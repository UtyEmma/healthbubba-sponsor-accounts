(there need to be a process for onboarding users) - registration (with their Healthbubba email) or maybe not

users - the sponsor account users (or use their Healthbubba login)
sponsors - (related to hb users)

beneficiaries - this is on healthbubba database

activity-log - (treat as notifications (notifications are for user level, activity log are for app level))
medical-access - 

plans - plan_features - plan_limits - plan_duration - (are we having yearly and monthly plans)

subscriptions - 

wallet 

transactions 

team 

enrollment-codes

--- consultations (hb db (appointments))

<!-- implement plan upgrade or downgrade. plan upgrade should be charged at a prorated rate while downgrade should be charged and effected in the next billing cycle -->



The basic idea is the same. but the beneficiary count and employee count is limited by the Included Beneficiaries and employee seat features taking into account extra seat for each account



impelemt beneficiaries for indidicual sponsor accounts and employees for business sponsors.

implemnt medical access requests for a workspace's beneficiaries.
- the user may select the beneficiary, the type of data represented by an enum which includes (Clinical Diagnosis (CLINICAL_RECORD), Prescription Records (PRESCRIPTION_RECORD), or Laboratory Results (LAB_RECORD)), and also an optional reason for the request

when a medical access request is sent, an email is to be sent to the beneficiary to accept or deny the request. The email will include a temporary signed route url to the medical access request review page where the user may allow or deny the request.

ignore for now viewing of the medical record requested as that will be handled later

