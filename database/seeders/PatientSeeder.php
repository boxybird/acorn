<?php

namespace Database\Seeders;

use App\Enums\IntakeStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Seed sample patients at different intake stages.
     */
    public function run(): void
    {
        $this->createCompletedPatient();
        $this->createSubmittedPatient();
        $this->createPartialPatient();
        $this->createNewPatient();
        $this->createSpanishSpeakingPatient();
        $this->createMultiChildPatient();
    }

    private function createCompletedPatient(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Maria Garcia',
            'email' => 'maria.garcia@example.com',
            'magic_link_token' => 'seed-maria-garcia',
            'magic_link_expires_at' => now()->addYear(),
        ]);

        $intake = Intake::factory()->create([
            'patient_id' => $patient->id,
            'child_name' => 'Sofia Garcia',
            'status' => IntakeStatus::Approved,
            'sync_status' => 'synced',
            'synced_at' => now()->subDays(2),
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'phone' => '(505) 555-0101',
                'email' => 'maria.garcia@example.com',
                'address' => '1234 Central Ave NW, Albuquerque, NM 87104',
                'preferred_language' => 'en',
                'has_secondary_guardian' => true,
                'secondary_guardian_name' => 'Carlos Garcia',
                'secondary_guardian_phone' => '(505) 555-0102',
                'secondary_guardian_email' => 'carlos.garcia@example.com',
                'referral_source' => 'pediatrician',
                'referring_provider' => 'Dr. Sarah Johnson',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'insurance',
            'data' => [
                'insurance_provider' => 'Blue Cross Blue Shield of NM',
                'policy_number' => 'BCB-987654321',
                'group_number' => 'GRP-12345',
                'policyholder_name' => 'Maria Garcia',
                'policyholder_dob' => '1990-03-15',
                'policyholder_relationship' => 'parent',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'child_information',
            'data' => [
                'child_first_name' => 'Sofia',
                'child_last_name' => 'Garcia',
                'child_dob' => '2021-06-12',
                'child_gender' => 'female',
                'pediatrician_name' => 'Dr. Sarah Johnson',
                'pediatrician_phone' => '(505) 555-0200',
                'school_name' => 'Sunshine Montessori',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'medical_history',
            'data' => [
                'has_autism_diagnosis' => true,
                'diagnosis_provider' => 'Dr. Michael Chen, UNM Developmental Pediatrics',
                'diagnosis_date' => '2024-09-20',
                'other_diagnoses' => 'Speech delay',
                'current_medications' => 'None',
                'allergies' => 'No known allergies',
                'prior_evaluations' => 'Speech evaluation at UNM (Jan 2024), OT evaluation at Lovelace (Mar 2024)',
                'prior_aba_therapy' => false,
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'developmental_concerns',
            'data' => [
                'first_words_age' => '24',
                'first_walking_age' => '14',
                'toilet_trained' => 'in_progress',
                'primary_concerns' => 'Limited verbal communication, difficulty with transitions, repetitive behaviors with toys.',
                'communication_level' => 'single_words',
                'behavioral_concerns' => 'Tantrums during transitions, occasional head-banging when frustrated.',
                'strengths' => 'Loves music, very affectionate with family, good fine motor skills, enjoys puzzles.',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'consent',
            'data' => [
                'consent_evaluation' => true,
                'consent_information_sharing' => true,
                'consent_photo_video' => 'yes',
                'guardian_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
                'signature_date' => '2026-03-01',
            ],
        ]);
    }

    private function createSubmittedPatient(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'David Chavez',
            'email' => 'david.chavez@example.com',
            'magic_link_token' => 'seed-david-chavez',
            'magic_link_expires_at' => now()->addYear(),
        ]);

        $intake = Intake::factory()->submitted()->create([
            'patient_id' => $patient->id,
            'child_name' => 'Isabella Chavez',
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'David',
                'last_name' => 'Chavez',
                'phone' => '(505) 555-0401',
                'email' => 'david.chavez@example.com',
                'address' => '456 Eubank Blvd NE, Albuquerque, NM 87112',
                'preferred_language' => 'en',
                'has_secondary_guardian' => true,
                'secondary_guardian_name' => 'Elena Chavez',
                'secondary_guardian_phone' => '(505) 555-0402',
                'secondary_guardian_email' => 'elena.chavez@example.com',
                'referral_source' => 'pediatrician',
                'referring_provider' => 'Dr. Lisa Romero',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'insurance',
            'data' => [
                'insurance_provider' => 'Molina Healthcare of NM',
                'policy_number' => 'MOL-555123456',
                'group_number' => 'GRP-44221',
                'policyholder_name' => 'David Chavez',
                'policyholder_dob' => '1992-07-10',
                'policyholder_relationship' => 'parent',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'child_information',
            'data' => [
                'child_first_name' => 'Isabella',
                'child_last_name' => 'Chavez',
                'child_dob' => '2022-09-03',
                'child_gender' => 'female',
                'pediatrician_name' => 'Dr. Lisa Romero',
                'pediatrician_phone' => '(505) 555-0450',
                'school_name' => 'Little Sprouts Preschool',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'medical_history',
            'data' => [
                'has_autism_diagnosis' => true,
                'diagnosis_provider' => 'Dr. Karen White, Lovelace Pediatric Neurology',
                'diagnosis_date' => '2025-11-15',
                'other_diagnoses' => 'Sensory processing disorder',
                'current_medications' => 'Melatonin 1mg (sleep)',
                'allergies' => 'Peanuts',
                'prior_evaluations' => 'Developmental eval at Lovelace (Oct 2025)',
                'prior_aba_therapy' => false,
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'developmental_concerns',
            'data' => [
                'first_words_age' => '20',
                'first_walking_age' => '13',
                'toilet_trained' => 'no',
                'primary_concerns' => 'Difficulty with social interaction, limited eye contact, repetitive hand movements.',
                'communication_level' => 'phrases',
                'behavioral_concerns' => 'Meltdowns in new environments, very rigid routines.',
                'strengths' => 'Excellent memory, loves animals, enjoys water play, recognizes all letters and numbers.',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'consent',
            'data' => [
                'consent_evaluation' => true,
                'consent_information_sharing' => true,
                'consent_photo_video' => 'no',
                'guardian_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg==',
                'signature_date' => '2026-03-08',
            ],
        ]);
    }

    private function createPartialPatient(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'James Thompson',
            'email' => 'james.thompson@example.com',
            'magic_link_token' => 'seed-james-thompson',
            'magic_link_expires_at' => now()->addYear(),
        ]);

        $intake = Intake::factory()->create([
            'patient_id' => $patient->id,
            'child_name' => 'Ethan Thompson',
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'James',
                'last_name' => 'Thompson',
                'phone' => '(505) 555-0301',
                'email' => 'james.thompson@example.com',
                'address' => '5678 Lomas Blvd NE, Albuquerque, NM 87110',
                'preferred_language' => 'en',
                'has_secondary_guardian' => false,
                'referral_source' => 'online',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'insurance',
            'data' => [
                'insurance_provider' => 'Presbyterian Health Plan',
                'policy_number' => 'PHP-123456789',
                'group_number' => 'GRP-99887',
                'policyholder_name' => 'James Thompson',
                'policyholder_dob' => '1988-11-22',
                'policyholder_relationship' => 'parent',
            ],
        ]);

        FormResponse::factory()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'child_information',
            'status' => 'in_progress',
            'data' => [
                'child_first_name' => 'Ethan',
                'child_last_name' => 'Thompson',
                'child_dob' => '2022-01-08',
                'child_gender' => 'male',
            ],
        ]);
    }

    private function createNewPatient(): void
    {
        Patient::factory()->create([
            'name' => 'Ashley Begay',
            'email' => 'ashley.begay@example.com',
            'magic_link_token' => 'seed-ashley-begay',
            'magic_link_expires_at' => now()->addYear(),
        ]);
    }

    private function createSpanishSpeakingPatient(): void
    {
        $patient = Patient::factory()->spanishSpeaking()->create([
            'name' => 'Rosa Martinez',
            'email' => 'rosa.martinez@example.com',
            'magic_link_token' => 'seed-rosa-martinez',
            'magic_link_expires_at' => now()->addYear(),
        ]);

        $intake = Intake::factory()->create([
            'patient_id' => $patient->id,
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'Rosa',
                'last_name' => 'Martinez',
                'phone' => '(505) 555-0501',
                'email' => 'rosa.martinez@example.com',
                'address' => '910 Bridge Blvd SW, Albuquerque, NM 87105',
                'preferred_language' => 'es',
                'has_secondary_guardian' => true,
                'secondary_guardian_name' => 'Diego Martinez',
                'secondary_guardian_phone' => '(505) 555-0502',
                'referral_source' => 'friend_family',
            ],
        ]);
    }

    private function createMultiChildPatient(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Sarah Williams',
            'email' => 'sarah.williams@example.com',
            'magic_link_token' => 'seed-sarah-williams',
            'magic_link_expires_at' => now()->addYear(),
        ]);

        $firstIntake = Intake::factory()->create([
            'patient_id' => $patient->id,
            'child_name' => 'Liam Williams',
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $firstIntake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'Sarah',
                'last_name' => 'Williams',
                'phone' => '(505) 555-0601',
                'email' => 'sarah.williams@example.com',
                'address' => '321 Rio Grande Blvd, Albuquerque, NM 87104',
                'preferred_language' => 'en',
                'has_secondary_guardian' => false,
                'referral_source' => 'pediatrician',
            ],
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $firstIntake->id,
            'schema_key' => 'child_information',
            'data' => [
                'child_first_name' => 'Liam',
                'child_last_name' => 'Williams',
                'child_dob' => '2021-03-10',
                'child_gender' => 'male',
                'pediatrician_name' => 'Dr. Amy Patel',
                'pediatrician_phone' => '(505) 555-0700',
            ],
        ]);

        $secondIntake = Intake::factory()->withoutChildName()->create([
            'patient_id' => $patient->id,
        ]);

        FormResponse::factory()->completed()->create([
            'intake_id' => $secondIntake->id,
            'schema_key' => 'demographics',
            'data' => [
                'first_name' => 'Sarah',
                'last_name' => 'Williams',
                'phone' => '(505) 555-0601',
                'email' => 'sarah.williams@example.com',
                'address' => '321 Rio Grande Blvd, Albuquerque, NM 87104',
                'preferred_language' => 'en',
                'has_secondary_guardian' => false,
                'referral_source' => 'pediatrician',
            ],
        ]);
    }
}
