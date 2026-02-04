<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Builder;

class BasePeriodModel extends BaseModel
{
    protected string $table_prefix = '';

    public function __construct(array $attributes = [])
    {
        global $period;
        parent::__construct($attributes);
        if ($period) {
            $this->table_prefix = 'ped' . preg_replace('/[^a-z0-9]/', '', $period) . '_';
        }
    }
    public function scopeCustomer(Builder $query): Builder
    {
        global $currentCustomer;
        return $query->where('customer_id', $currentCustomer->id);
    }
    public function addParam($key, $values): static
    {
        if (!$this->arrParams) {
            $this->arrParams = (array)$this->params;
        }
        $this->arrParams[$key] = $values;
        return $this;
    }
    public function updateParam($key, $value): static
    {
        if (!$this->arrParams) {
            $this->arrParams = (array)$this->params;
        }
        if ($this->arrParams) {
            if (!empty($this->arrParams[$key]) && $this->arrParams[$key]) {
                $this->arrParams[$key] = (array)$this->arrParams[$key];
                if (is_array($value)) {
                    $this->arrParams[$key] = array_values(Helpers::ArrMerge($this->arrParams[$key], $value));
                } else {
                    if (is_numeric($value)) {
                        if (!in_array($value, $this->arrParams[$key])) {
                            $this->arrParams[$key][] = (int)$value;
                        }
                    } else {
                        if (!in_array($value, $this->arrParams[$key])) {
                            $this->arrParams[$key][] = (string)$value;
                        }
                    }
                }
            } else {
                if (is_array($value)) {
                    $this->arrParams[$key] = $value;
                } else {
                    if (is_numeric($value)) {
                        $this->arrParams[$key] = [(int)$value];
                    } else {
                        $this->arrParams[$key] = [(string)$value];
                    }
                }
            }
        } else {
            if (is_array($value)) {
                $this->arrParams[$key] = $value;
            } else {
                if (is_numeric($value)) {
                    $this->arrParams[$key] = [(int)$value];
                } else {
                    $this->arrParams[$key] = [(string)$value];
                }
            }
        }
        return $this;
    }
    public function removeParam($key, $value = ''): static
    {
        if (!$this->arrParams) {
            $this->arrParams = (array)$this->params;
        }
        if ($this->arrParams) {
            if (!empty($this->arrParams[$key])) {
                if ($value && is_array($this->arrParams[$key])) {
                    if (is_array($value)) {
                        if ($v = array_diff($this->arrParams[$key], [$value])) {
                            $this->arrParams[$key] = array_values($v);
                        } else {
                            $this->arrParams[$key] = [];
                        }
                    } else {
                        if (is_numeric($value)) {
                            $value = (int)$value;
                        }
                        if (in_array($value, $this->arrParams[$key])) {
                            if ($v = array_diff($this->arrParams[$key], [$value])) {
                                $this->arrParams[$key] = array_values($v);
                            } else {
                                $this->arrParams[$key] = [];
                            }
                        }
                    }
                } else {
                    unset($this->arrParams[$key]);
                }
            }
        }
        return $this;
    }
    public function toParams(): static
    {
        if (!empty($this->arrParams)) {
            $this->params = (object)$this->arrParams;
        } else {
            $this->params = (object)[];
        }
        return $this;
    }
}
