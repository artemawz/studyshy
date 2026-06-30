<?php

namespace Database\Seeders;

use App\Models\BoardPost;
use App\Models\Chat;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\Interest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $filterInterests = config('studyshy.interests');

        foreach ($filterInterests as $name) {
            Interest::firstOrCreate(['name' => $name]);
        }

        $students = [
            ['pub_name' => 'Student #a3f', 'uni' => 'Hochschule Bochum', 'course' => 'Informatik', 'semester' => 3, 'interests' => ['Kochen', 'Fußball'], 'avatar' => 12, 'bio' => 'Suche Lernpartner für Analysis und jemanden zum Fußball schauen.'],
            ['pub_name' => 'Student #7c2', 'uni' => 'Ruhr-Universität Bochum', 'course' => 'Elektrotechnik', 'semester' => 5, 'interests' => ['Formel 1', 'Flugzeuge'], 'avatar' => 33, 'bio' => 'Technik-Nerd, gerne über Projekte und Praktika austauschen.'],
            ['pub_name' => 'Student #19e', 'uni' => 'Ruhr-Universität Bochum', 'course' => 'Medizin', 'semester' => 7, 'interests' => ['Umweltschutz', 'Kochen'], 'avatar' => 45, 'bio' => 'Medizinstudentin – suche Gleichgesinnte für Lerngruppen und entspannte Abende.'],
            ['pub_name' => 'Student #b81', 'uni' => 'Hochschule Bochum', 'course' => 'BWL', 'semester' => 2, 'interests' => ['Musik', 'Kunst'], 'avatar' => 8, 'bio' => 'Im zweiten Semester und noch auf der Suche nach einem guten Studienstart.'],
            ['pub_name' => 'Student #4d0', 'uni' => 'Hochschule Bochum', 'course' => 'Informatik', 'semester' => 6, 'interests' => ['Backen', 'Menschenrechte'], 'avatar' => 22, 'bio' => 'Interessiert an Open Source und gesellschaftlichen Themen.'],
            ['pub_name' => 'Student #f52', 'uni' => 'Hochschule Bochum', 'course' => 'Lehramt', 'semester' => 4, 'interests' => ['Kunst', 'Musik', 'Umweltschutz'], 'avatar' => 51, 'bio' => 'Lehramtsstudent – gerne über Didaktik und Praktika sprechen.'],
            ['pub_name' => 'Student #2aa', 'uni' => 'Ruhr-Universität Bochum', 'course' => 'Jura', 'semester' => 8, 'interests' => ['Fußball', 'Formel 1'], 'avatar' => 17, 'bio' => 'Jura im Endspurt – suche Motivation und Lernpartner.'],
            ['pub_name' => 'Student #c07', 'uni' => 'Ruhr-Universität Bochum', 'course' => 'Informatik', 'semester' => 10, 'interests' => ['Flugzeuge', 'Kochen', 'Musik'], 'avatar' => 28, 'bio' => 'Master-Student, offen für Networking und Side Projects.'],
            ['pub_name' => 'Student #88b', 'uni' => 'Hochschule Bochum', 'course' => 'Germanistik', 'semester' => 3, 'interests' => ['Kunst', 'Backen'], 'avatar' => 29, 'bio' => 'Liebe Literatur und gutes Brot – immer offen für neue Kontakte.'],
            ['pub_name' => 'Student #e14', 'uni' => 'Ruhr-Universität Bochum', 'course' => 'Elektrotechnik', 'semester' => 1, 'interests' => ['Fußball', 'Musik'], 'avatar' => 41, 'bio' => 'Erstes Semester – suche Anschluss und Tipps vom Studienstart.'],
        ];

        $createdUsers = [];

        foreach ($students as $index => $data) {
            $user = User::create([
                'email' => 'student'.($index + 1).'@demo.studyshy',
                'password' => Hash::make('password123'),
                'pub_name' => $data['pub_name'],
                'uni' => $data['uni'],
                'bio' => $data['bio'],
                'avatar_url' => 'https://i.pravatar.cc/150?img='.$data['avatar'],
            ]);

            $user->courses()->create([
                'name' => $data['course'],
                'degree' => $data['semester'] > 6 ? 'Master' : 'Bachelor',
                'semester' => $data['semester'],
            ]);

            $interestIds = collect($data['interests'])->map(
                fn (string $name) => Interest::firstOrCreate(['name' => $name])->id
            );
            $user->interests()->sync($interestIds);

            $createdUsers[] = $user;
        }

        // Demo-Account mit vorbefüllten Chats (student1@demo.studyshy / password123)
        $demoUser = $createdUsers[0];

        // Bestehende Chats setzen eine Freundschaft voraus
        Friendship::create([
            'requester_id' => $createdUsers[1]->id,
            'addressee_id' => $demoUser->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'requester_id' => $createdUsers[4]->id,
            'addressee_id' => $demoUser->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Offene Freundschaftsanfragen an den Demo-Account (für die Glocke/Benachrichtigungen)
        Friendship::create([
            'requester_id' => $createdUsers[3]->id,
            'addressee_id' => $demoUser->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
        Friendship::create([
            'requester_id' => $createdUsers[8]->id,
            'addressee_id' => $demoUser->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $chat1 = Chat::create([
            'status' => Chat::STATUS_ACCEPTED,
            'requested_by' => $createdUsers[1]->id,
        ]);
        $chat1->participants()->attach([$demoUser->id, $createdUsers[1]->id]);
        Message::create([
            'chat_id' => $chat1->id,
            'sender_id' => $createdUsers[1]->id,
            'text' => 'Hey! Habe gesehen, dass du auch Elektrotechnik studierst.',
            'sent_at' => now()->subHour(),
        ]);
        Message::create([
            'chat_id' => $chat1->id,
            'sender_id' => $demoUser->id,
            'text' => 'Ja genau, bin im 5. Semester. Suchst du eine Lerngruppe?',
            'sent_at' => now()->subMinutes(45),
        ]);
        Message::create([
            'chat_id' => $chat1->id,
            'sender_id' => $createdUsers[1]->id,
            'text' => 'Klingt gut, treffen wir uns morgen in der Mensa?',
            'sent_at' => now()->subMinutes(12),
        ]);

        $chat2 = Chat::create([
            'status' => Chat::STATUS_ACCEPTED,
            'requested_by' => $createdUsers[4]->id,
        ]);
        $chat2->participants()->attach([$demoUser->id, $createdUsers[4]->id]);
        Message::create([
            'chat_id' => $chat2->id,
            'sender_id' => $createdUsers[4]->id,
            'text' => 'Hast du schon die Übungsblätter für Algorithmen?',
            'sent_at' => now()->subHours(5),
        ]);

        // Lerngruppen
        $group1 = Group::create([
            'created_by' => $createdUsers[1]->id,
            'name' => 'Analysis I – Lerngruppe',
            'description' => 'Wir treffen uns wöchentlich, um Übungsblätter zu rechnen und uns auf die Klausur vorzubereiten.',
            'uni' => 'Ruhr-Universität Bochum',
            'course' => 'Elektrotechnik',
        ]);
        $group1->members()->attach($createdUsers[1]->id, ['role' => 'owner', 'joined_at' => now()]);
        $group1->members()->attach($demoUser->id, ['role' => 'member', 'joined_at' => now()]);
        $group1->members()->attach($createdUsers[6]->id, ['role' => 'member', 'joined_at' => now()]);
        GroupMessage::create([
            'group_id' => $group1->id,
            'sender_id' => $createdUsers[1]->id,
            'text' => 'Willkommen! Nächstes Treffen Donnerstag 14 Uhr in der Bib.',
            'sent_at' => now()->subHours(3),
        ]);

        $group2 = Group::create([
            'created_by' => $createdUsers[4]->id,
            'name' => 'Informatik Erstis HS Bochum',
            'description' => 'Austausch für alle Erstsemester:innen der Informatik. Fragen, Tipps & Treffen.',
            'uni' => 'Hochschule Bochum',
            'course' => 'Informatik',
        ]);
        $group2->members()->attach($createdUsers[4]->id, ['role' => 'owner', 'joined_at' => now()]);
        $group2->members()->attach($createdUsers[0]->id, ['role' => 'member', 'joined_at' => now()]);

        // Schwarzes Brett
        BoardPost::create([
            'user_id' => $createdUsers[2]->id,
            'category' => 'Lernpartner',
            'title' => 'Suche Lernpartner für Anatomie',
            'body' => 'Hat jemand Lust, zusammen für die Anatomie-Klausur zu lernen? Gerne in der Bib oder online.',
        ]);
        BoardPost::create([
            'user_id' => $createdUsers[1]->id,
            'category' => 'Material',
            'title' => 'Mitschriften ET-Grundlagen zu teilen',
            'body' => 'Ich habe vollständige Mitschriften aus dem letzten Semester. Schreibt mich an!',
        ]);
        BoardPost::create([
            'user_id' => $createdUsers[8]->id,
            'category' => 'Wohnen',
            'title' => 'WG-Zimmer in Bochum-Querenburg frei',
            'body' => 'Ab nächstem Monat wird ein Zimmer frei. Nähe Campus, 350€ warm.',
        ]);

        // Events
        Event::create([
            'created_by' => $createdUsers[1]->id,
            'title' => 'Gemeinsames Lernen in der UB',
            'description' => 'Wir treffen uns zum konzentrierten Lernen und machen zwischendurch Kaffeepausen.',
            'location' => 'Universitätsbibliothek, Ebene 5',
            'uni' => 'Ruhr-Universität Bochum',
            'starts_at' => now()->addDays(2)->setTime(14, 0),
        ])->participants()->attach([$createdUsers[1]->id => ['joined_at' => now()], $demoUser->id => ['joined_at' => now()]]);

        Event::create([
            'created_by' => $createdUsers[4]->id,
            'title' => 'Ersti-Stammtisch',
            'description' => 'Lockeres Kennenlernen bei Getränken. Kommt vorbei!',
            'location' => 'Mensa-Vorplatz',
            'uni' => 'Hochschule Bochum',
            'starts_at' => now()->addDays(5)->setTime(19, 0),
        ])->participants()->attach([$createdUsers[4]->id => ['joined_at' => now()]]);
    }
}
