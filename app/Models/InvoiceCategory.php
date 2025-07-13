<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceCategory extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = ['name','status'];

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class, 'category_id');
    }
}
