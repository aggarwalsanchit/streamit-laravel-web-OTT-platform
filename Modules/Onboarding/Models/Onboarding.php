<?php

namespace Modules\Onboarding\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Onboarding extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'onboardings';
    protected $fillable=['title','description','file_url','status'];

}
