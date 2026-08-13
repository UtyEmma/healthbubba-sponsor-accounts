<?php

namespace App\Models\Consultations;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model {
    
    protected $fillable = ['workspace_id', 'beneficiary_id', 'appointment_id', 'doctor_id', 'doctor_type'];

    // Request
    // add an api endpoint for updating the consultation usage by the app. The post request should have the appointment_id, sponsor_id, and the patient_id 
    
    // - when it is created, then the user should be able to 
    
}
