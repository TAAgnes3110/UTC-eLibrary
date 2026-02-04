<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasFactory;
    public array $arrParams = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->arrParams = $this->getArrParams();
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->toParams();
        });
    }

    public function getArrParams()
    {
        if (!empty($this->params) && is_array($this->params)) {
            return $this->params;
        }
        if (!empty($this->params) && is_string($this->params)) {
            $decoded = json_decode($this->params, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function addParam($key, $value)
    {
        $this->arrParams[$key] = $value;
        return $this;
    }

    public function updateParam($key, $value)
    {
        if (isset($this->arrParams[$key])) {
            $this->arrParams[$key] = $value;
        }
        return $this;
    }

    public function removeParam($key)
    {
        if (isset($this->arrParams[$key])) {
            unset($this->arrParams[$key]);
        }
        return $this;
    }

    public function increaseParamValue($key, $amount = 1)
    {
        if (isset($this->arrParams[$key]) && is_numeric($this->arrParams[$key])) {
            $this->arrParams[$key] += $amount;
        } else {
            $this->arrParams[$key] = $amount;
        }
        return $this;
    }

    public function toParams()
    {
        $this->params = $this->arrParams;
        return $this;
    }

    public function scopeCustomer(Builder $query): Builder
    {
        return $query;
    }
}
