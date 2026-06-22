<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteClinicalMessages(nullablePatient: true);

            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE clinical_messages MODIFY dossier_patient_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $hasInternalWithoutPatient = DB::table('clinical_messages')
                ->whereNull('dossier_patient_id')
                ->exists();

            if (! $hasInternalWithoutPatient) {
                $this->rebuildSqliteClinicalMessages(nullablePatient: false);
            }

            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE clinical_messages MODIFY dossier_patient_id BIGINT UNSIGNED NOT NULL');
        }
    }

    private function rebuildSqliteClinicalMessages(bool $nullablePatient): void
    {
        $patientNullSql = $nullablePatient ? '' : ' not null';
        $patientDeleteAction = $nullablePatient ? 'set null' : 'cascade';

        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement(<<<SQL
CREATE TABLE clinical_messages_new (
    id integer primary key autoincrement not null,
    hopital_id integer not null,
    dossier_patient_id integer{$patientNullSql},
    consultation_id integer,
    parent_id integer,
    sender_id integer,
    sender_type varchar not null default 'user',
    category varchar not null,
    priority varchar not null default 'normal',
    subject varchar not null,
    body text not null,
    status varchar not null default 'sent',
    sent_at datetime,
    metadata text,
    created_at datetime,
    updated_at datetime,
    thread_id integer,
    message_type varchar not null default 'patient',
    recipient_summary varchar,
    last_activity_at datetime,
    foreign key(hopital_id) references hopitals(id) on delete cascade,
    foreign key(dossier_patient_id) references dossier_patients(id) on delete {$patientDeleteAction},
    foreign key(consultation_id) references consultations(id) on delete set null,
    foreign key(parent_id) references clinical_messages(id) on delete set null,
    foreign key(sender_id) references users(id) on delete set null
)
SQL);

        DB::statement(<<<SQL
INSERT INTO clinical_messages_new (
    id, hopital_id, dossier_patient_id, consultation_id, parent_id, sender_id,
    sender_type, category, priority, subject, body, status, sent_at, metadata,
    created_at, updated_at, thread_id, message_type, recipient_summary, last_activity_at
)
SELECT
    id, hopital_id, dossier_patient_id, consultation_id, parent_id, sender_id,
    sender_type, category, priority, subject, body, status, sent_at, metadata,
    created_at, updated_at, thread_id, message_type, recipient_summary, last_activity_at
FROM clinical_messages
SQL);

        DB::statement('DROP TABLE clinical_messages');
        DB::statement('ALTER TABLE clinical_messages_new RENAME TO clinical_messages');

        DB::statement('CREATE INDEX clinical_messages_hopital_id_dossier_patient_id_sent_at_index on clinical_messages (hopital_id, dossier_patient_id, sent_at)');
        DB::statement('CREATE INDEX clinical_messages_sender_id_sent_at_index on clinical_messages (sender_id, sent_at)');
        DB::statement('CREATE INDEX clinical_messages_internal_index on clinical_messages (hopital_id, message_type, status, last_activity_at)');
        DB::statement('CREATE INDEX clinical_messages_thread_id_index on clinical_messages (thread_id)');
        DB::statement('CREATE INDEX clinical_messages_message_type_index on clinical_messages (message_type)');
        DB::statement('CREATE INDEX clinical_messages_last_activity_at_index on clinical_messages (last_activity_at)');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
