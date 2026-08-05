<?php

namespace App\Concerns;

use App\Enums\Account\Status;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasStatus {

    protected $status;

    public function initializeHasStatus() {
        $this->status = $this->setStatusEnum();
        $this->fillable[] = 'status';

        $this->mergeCasts([
            'status' => $this->status
        ]);

        if(!isset($this->attributes['status'])) {
            if($this->status::tryFrom(Status::ACTIVE->value)) {
                $this->setAttribute('status', $this->status::ACTIVE);
            }
        }
    }

    protected function setStatusEnum(){
        return Status::class;
    }

    public function scopeIsActive($query){
        $query->whereStatus(Status::ACTIVE);
    }

}