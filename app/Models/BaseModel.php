<?php

namespace App\Models;

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
        if (!empty($this->params)) {
            $params = json_decode(json_encode($this->params), true);
            return is_array($params) ? $params : [];
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
        $this->params = (object)$this->arrParams;
        return $this;
    }

    public function scopeCustomer($query)
    {
        // Implement customer scope logic if needed
        return $query;
    }
}
