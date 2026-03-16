<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Tenant\BelongToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractTenant extends Model
{
    use BelongToTenant;
    use HasFactory;
}
