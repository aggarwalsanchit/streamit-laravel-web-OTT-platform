<?php

namespace App\Support;

/**
 * Detects how much CPU this host actually has available, so CPU-bound work
 * (primarily ffmpeg transcoding) can size itself to the machine instead of
 * using a hardcoded thread count.
 *
 * Detection is container-aware: on Docker/Kubernetes, /proc/cpuinfo reports the
 * *host's* cores, not the cgroup CPU limit the container is actually allowed to
 * use. A 2-CPU container on a 64-core host would otherwise size itself for 64
 * cores and get throttled hard. cgroup quota is therefore checked first and the
 * smaller of (quota, physical cores) wins.
 *
 * Everything here is best-effort and must never throw — a bad reading degrades
 * to a conservative default rather than breaking application bootstrap.
 */
class CpuCoreDetector
{
    /**
     * Upper bound on ffmpeg threads. libx264's scaling flattens out well before
     * this, and beyond it the extra context switching costs more than it gains.
     */
    public const MAX_THREADS = 16;

    /** Used when every detection strategy fails. Deliberately pessimistic. */
    public const FALLBACK_CORES = 2;

    private static ?int $cached = null;

    /**
     * Available cores, detected once per process.
     */
    public static function cores(): int
    {
        return self::$cached ??= self::detect();
    }

    /**
     * Clear the memoised value. Intended for tests.
     */
    public static function flush(): void
    {
        self::$cached = null;
    }

    /**
     * Thread count to hand to ffmpeg.
     *
     * One core is held back so the queue worker's own PHP process, the web
     * server and the database aren't fully starved while a transcode runs —
     * on a low-spec box, an ffmpeg job that saturates every core makes the
     * whole site unresponsive rather than merely slow.
     */
    public static function ffmpegThreads(): int
    {
        return self::threadsForCores(self::cores());
    }

    /**
     * Pure core-count -> thread-count mapping, split out so it can be tested
     * without depending on the machine the tests happen to run on.
     */
    public static function threadsForCores(int $cores): int
    {
        if ($cores < 1) {
            $cores = self::FALLBACK_CORES;
        }

        return max(1, min(self::MAX_THREADS, $cores - 1));
    }

    /**
     * Run detection, ignoring any memoised value.
     */
    public static function detect(): int
    {
        $physical = self::physicalCores();
        $quota = self::cgroupQuota();

        // A cgroup quota can legitimately exceed the physical core count
        // (over-committed limits); the real ceiling is still the hardware.
        if ($quota !== null) {
            return max(1, min($quota, $physical));
        }

        return $physical;
    }

    /**
     * Cores visible to the OS, ignoring container limits.
     */
    private static function physicalCores(): int
    {
        // Windows exposes this without needing to spawn a process.
        $windows = getenv('NUMBER_OF_PROCESSORS');
        if ($windows !== false && ctype_digit(trim((string) $windows))) {
            $count = (int) trim((string) $windows);
            if ($count > 0) {
                return $count;
            }
        }

        // Linux: counting /proc/cpuinfo avoids shelling out entirely, which
        // matters on hosts where exec()/shell_exec() are disabled.
        $cpuinfo = @file_get_contents('/proc/cpuinfo');
        if (is_string($cpuinfo) && $cpuinfo !== '') {
            $count = preg_match_all('/^processor\s*:/mi', $cpuinfo);
            if ($count > 0) {
                return $count;
            }
        }

        // macOS/BSD, plus Linux hosts without a readable /proc.
        foreach (['nproc', 'sysctl -n hw.ncpu'] as $command) {
            $output = self::runShell($command);
            if ($output !== null && ctype_digit($output)) {
                $count = (int) $output;
                if ($count > 0) {
                    return $count;
                }
            }
        }

        return self::FALLBACK_CORES;
    }

    /**
     * Effective core count implied by a cgroup CPU quota, or null when the host
     * isn't containerised / has no quota set.
     */
    private static function cgroupQuota(): ?int
    {
        // cgroup v2: "$MAX $PERIOD", where $MAX is "max" when unlimited.
        $v2 = @file_get_contents('/sys/fs/cgroup/cpu.max');
        if (is_string($v2) && $v2 !== '') {
            $parts = preg_split('/\s+/', trim($v2)) ?: [];
            if (count($parts) >= 2 && $parts[0] !== 'max') {
                return self::quotaToCores((float) $parts[0], (float) $parts[1]);
            }
        }

        // cgroup v1: quota of -1 means unlimited.
        $quota = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
        $period = @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us');

        if (is_string($quota) && is_string($period)) {
            $quota = (float) trim($quota);
            $period = (float) trim($period);

            if ($quota > 0) {
                return self::quotaToCores($quota, $period);
            }
        }

        return null;
    }

    /**
     * A quota of 150ms per 100ms period means 1.5 cores; round up so a
     * fractional allowance is never reported as zero.
     */
    private static function quotaToCores(float $quota, float $period): ?int
    {
        if ($quota <= 0 || $period <= 0) {
            return null;
        }

        return max(1, (int) ceil($quota / $period));
    }

    /**
     * Best-effort shell execution. Returns null when the command is unavailable
     * or shell functions are disabled by php.ini.
     */
    private static function runShell(string $command): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('shell_exec', $disabled, true)) {
            return null;
        }

        try {
            $output = @shell_exec($command.' 2>/dev/null');
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($output)) {
            return null;
        }

        $output = trim($output);

        return $output === '' ? null : $output;
    }
}