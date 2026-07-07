<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Erlaubte Hochschulen
    |--------------------------------------------------------------------------
    */

    'universities' => [
        'Hochschule Bochum',
        'Ruhr-Universität Bochum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abschlussarten
    |--------------------------------------------------------------------------
    */

    'degrees' => [
        'Bachelor',
        'Master',
    ],

    /*
    |--------------------------------------------------------------------------
    | Studiengänge je Hochschule (Stand WiSe 2026/27)
    |--------------------------------------------------------------------------
    | Pro Hochschule eine Liste. Bachelor-/Master-Varianten sind zu einem
    | Studiengangsnamen zusammengefasst. Werden mit den real in der DB
    | vorkommenden Studiengängen der jeweiligen Hochschule zusammengeführt.
    | Die Schlüssel müssen exakt den Werten unter "universities" entsprechen.
    */

    'courses' => [

        'Hochschule Bochum' => [
            'Accounting and Taxation',
            'Angewandte Gesundheitswissenschaften',
            'Angewandte Informatik',
            'Angewandte Nachhaltigkeit',
            'Architektur',
            'Architektur Mediamanagement',
            'Architektur Projektentwicklung',
            'Bauingenieurwesen',
            'Betriebswirtschaftslehre',
            'Clinical Research Management',
            'Digital Construction Management',
            'Elektrotechnik',
            'Ergotherapie',
            'Geodäsie',
            'Geoinformatik',
            'Geothermal Energy Systems',
            'Gesundheit und Diversity in der Arbeit',
            'Gesundheitsbezogene Soziale Arbeit',
            'Gesundheitsökonomie',
            'Gesundheitspsychologie',
            'Gesundheitswissenschaften',
            'Hebammenwissenschaft',
            'Informatik',
            'International Business and Management',
            'International Management',
            'Logopädie',
            'Management für Ingenieur- und Naturwissenschaften',
            'Management für Pflege- und Gesundheitsberufe',
            'Management nachhaltiger Innovationen im Gesundheitswesen',
            'Maschinenbau',
            'Mechatronics',
            'Mechatronik',
            'Mechatronische Systeme',
            'Nachhaltige Entwicklung',
            'Nachhaltiges Management in der Gesundheitswirtschaft',
            'Pflege',
            'Pflege- und Gesundheitspädagogik',
            'Pflegewissenschaft',
            'Physiotherapie',
            'Regenerative Energiesysteme',
            'Therapiewissenschaften',
            'Umweltinformatik',
            'Umweltingenieurwesen',
            'Vermessung',
            'Wirtschafts- und Industrieinformatik',
            'Wirtschaftsinformatik',
            'Wirtschaftsingenieurwesen',
            'Wirtschaftsingenieurwesen Bau',
            'Wirtschaftsingenieurwesen Elektrotechnik',
            'Wirtschaftsingenieurwesen Maschinenbau',
        ],

        'Ruhr-Universität Bochum' => [
            'Accounting and Auditing',
            'Allgemeine und Vergleichende Literaturwissenschaft',
            'Angewandte Informatik',
            'Anglistik/Amerikanistik',
            'Arabistik und Islamwissenschaft',
            'Archäologische Wissenschaften',
            'Bauingenieurwesen',
            'Biochemie',
            'Biologie',
            'Chemie',
            'Elektrotechnik und Informationstechnik',
            'Erziehungswissenschaft',
            'Evangelische Theologie',
            'Gender Studies',
            'Geographie',
            'Geowissenschaften',
            'Germanistik',
            'Geschichte',
            'Gesundheitswissenschaft',
            'Informatik',
            'IT-Sicherheit / Informationstechnik',
            'Japanologie',
            'Katholische Theologie',
            'Klassische Philologie',
            'Koreanistik',
            'Kunstgeschichte',
            'Linguistik',
            'Management and Economics',
            'Maschinenbau',
            'Mathematik',
            'Medienwissenschaft',
            'Medizin',
            'Orientalistik',
            'Pädagogik',
            'Philosophie',
            'Physik',
            'Politikwissenschaft',
            'Psychologie',
            'Rechtswissenschaft',
            'Religionswissenschaft',
            'Romanische Philologie',
            'Sales Engineering and Product Management',
            'Sinologie',
            'Slavistik',
            'Sozialwissenschaft',
            'Soziologie',
            'Sportwissenschaft',
            'Theaterwissenschaft',
            'Umwelttechnik und Ressourcenmanagement',
            'Wirtschaftswissenschaft',
            'Wirtschaftsingenieurwesen',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Vordefinierte Interessen (Benutzer können weitere über „Andere“ anlegen)
    |--------------------------------------------------------------------------
    */

    'interests' => [
        'Flugzeuge',
        'Kochen',
        'Backen',
        'Formel 1',
        'Fußball',
        'Umweltschutz',
        'Menschenrechte',
        'Kunst',
        'Musik',
    ],

];
