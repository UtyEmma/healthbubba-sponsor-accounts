<?php

namespace App\Models\Consultations;

use App\Enums\AppointmentStatus;
use App\Models\Beneficiary;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model {
    
    protected $connection = 'main_sql';

    protected $fillable = ['appointment_id', 'patient_id', 'doctor_id', 'date', 'time', 'status'];
    protected $primaryKey = 'appointment_id';
    protected $keyType = 'string';

    protected $casts = [
        'status' => AppointmentStatus::class
    ];

    function patient() {
        return $this->belongsTo(Beneficiary::class, 'patient_id');
    }

    function doctor(){
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }   


}
