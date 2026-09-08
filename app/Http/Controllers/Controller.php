<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'status' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'status' => false,
            'message' => $error,
        ];

        if (! empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    protected string $exportClass = '';

    /**
     * Export modal sends `columns` as repeated keys (array) or a comma-separated string.
     *
     * @return list<string>
     */
    protected function normalizedExportColumns(Request $request): array
    {
        $raw = $request->input('columns');
        if (is_array($raw)) {
            return array_values(array_filter(
                array_map(static fn ($v) => trim((string) $v), $raw),
                static fn (string $v) => $v !== ''
            ));
        }

        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $v) => $v !== ''
        ));
    }

    public function export(Request $request)
    {
        $columns = $this->normalizedExportColumns($request);
        if ($columns === []) {
            abort(422, 'No columns selected');
        }

        $type = $request->file_type;
        $casttype = $request->type ?? $request->casttype;

        $dateRange = explode(' to ', $request->date_range);
        if (count($dateRange) == 1) {
            $dateRange[1] = $dateRange[0];
        }

        if (! empty($this->exportClass)) {
            switch ($type) {
                case 'csv':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.csv', \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'xlsx':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.xlsx', \Maatwebsite\Excel\Excel::XLSX);
                    break;
                case 'xls':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.xls', \Maatwebsite\Excel\Excel::XLS);
                    break;
                case 'ods':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.ods', \Maatwebsite\Excel\Excel::ODS);
                    break;
                case 'html':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.html', \Maatwebsite\Excel\Excel::HTML);
                    break;
                case 'pdf':
                    return Excel::download(new $this->exportClass($columns, $dateRange, $casttype), 'file.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
                    break;
            }
        }

        return abort(500);
    }
}
