<?php
namespace App\Enums\Transactions;

enum TransactionStatus {

    case PENDING;
    case COMPLETED;
    case FAILED;
    

}