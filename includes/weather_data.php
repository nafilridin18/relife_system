<?php
// Shared Hydrological and Weather Data Provider for ReliefX

function getFloodMonitoringStations(): array {
    return [
        'kurigram' => [
            'station_id'        => 'BD-FFWC-KGM',
            'station_name_bn'   => 'কুড়িগ্রাম চর ও ধরলা নদী পয়েন্ট',
            'station_name_en'   => 'Kurigram Char & Dharla River Point',
            'district_bn'       => 'কুড়িগ্রাম',
            'district_en'       => 'Kurigram',
            'division_bn'       => 'রংপুর',
            'division_en'       => 'Rangpur',
            'river_bn'          => 'ধরলা ও ব্রহ্মপুত্র নদী',
            'river_en'          => 'Dharla & Brahmaputra Rivers',
            'lat'               => 25.8073,
            'lon'               => 89.6295,
            'water_level_m'     => 15.20,
            'danger_level_m'    => 13.50,
            'delta_m'           => 1.70,
            'river_trend'       => 'rising',
            'alert_level'       => 'CRITICAL',
            'alert_color'       => '#FF6B6B',
            'weather'           => [
                'condition_bn'    => 'অতি ভারী বর্ষণ ও বজ্রঝড়',
                'condition_en'    => 'Torrential Monsoon Downpour & Thunderstorm',
                'temp_c'          => 26.5,
                'feels_like_c'    => 31.0,
                'rainfall_24h_mm' => 145.8,
                'rainfall_72h_mm' => 285.0,
                'humidity_pct'    => 96,
                'wind_kmh'        => 38,
                'wind_dir'        => 'SE (দক্ষিণ-পূর্ব)',
                'pressure_hpa'    => 1002,
                'visibility_km'   => 3.2
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'ভারী বৃষ্টি', 'condition_en' => 'Heavy Rain', 'rain_mm' => 145, 'temp' => '27° / 24°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'অতি ভারী বর্ষণ', 'condition_en' => 'Torrential Rain', 'rain_mm' => 95, 'temp' => '26° / 23°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'বজ্রসহ বৃষ্টি', 'condition_en' => 'Thunderstorms', 'rain_mm' => 45, 'temp' => '28° / 24°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'নদীর পানি বিপদসীমার ১৭০ সেমি উপরে। চিলমারী ও রৌমারী চরাঞ্চলের বাসিন্দাদের অবিলম্বে নৌবাহিনীর রেসকিউ টিম বা নিকটস্থ ফ্লাড সেন্টারে আশ্রয় নিতে হবে। গবাদিপশু উঁচু বাধে সরিয়ে নিন।',
            'advisory_en'       => 'Water level is 1.70m above danger mark. Residents in Chilmari and Rowmari chars must evacuate to certified shelters immediately. Move livestock to elevated embankments.',
            'shelters'          => [
                ['name_bn' => 'চিলমারী সরকারি উচ্চ বিদ্যালয় আশ্রয়কেন্দ্র', 'name_en' => 'Chilmari Govt High School Shelter', 'capacity' => 600, 'contact' => '01711-234501'],
                ['name_bn' => 'রৌমারী সিজি জামান মডেল স্কুল', 'name_en' => 'Rowmari CG Zaman Model School', 'capacity' => 450, 'contact' => '01711-234502'],
                ['name_bn' => 'কুড়িগ্রাম স্টেডিয়াম ফ্লাড ক্যাম্প', 'name_en' => 'Kurigram Stadium Relief Camp', 'capacity' => 1200, 'contact' => '01711-234503']
            ]
        ],
        'sunamganj' => [
            'station_id'        => 'BD-FFWC-SNM',
            'station_name_bn'   => 'সুনামগঞ্জ সদর ও সুরমা নদী পয়েন্ট',
            'station_name_en'   => 'Sunamganj Sadar & Surma River Point',
            'district_bn'       => 'সুনামগঞ্জ',
            'district_en'       => 'Sunamganj',
            'division_bn'       => 'সিলেট',
            'division_en'       => 'Sylhet',
            'river_bn'          => 'সুরমা ও যাদুকাটা নদী',
            'river_en'          => 'Surma & Jadukata Rivers',
            'lat'               => 25.0658,
            'lon'               => 91.4073,
            'water_level_m'     => 8.45,
            'danger_level_m'    => 7.80,
            'delta_m'           => 0.65,
            'river_trend'       => 'rising',
            'alert_level'       => 'CRITICAL',
            'alert_color'       => '#FF6B6B',
            'weather'           => [
                'condition_bn'    => 'মেঘালয় সীমান্ত পাহাড়ি ঢল ও অবিরাম বৃষ্টি',
                'condition_en'    => 'Flash Flood Influx & Persistent Rain',
                'temp_c'          => 25.0,
                'feels_like_c'    => 29.5,
                'rainfall_24h_mm' => 180.2,
                'rainfall_72h_mm' => 340.0,
                'humidity_pct'    => 98,
                'wind_kmh'        => 32,
                'wind_dir'        => 'E (পূর্ব)',
                'pressure_hpa'    => 1000,
                'visibility_km'   => 2.5
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'পাহাড়ি ঢল ও বৃষ্টি', 'condition_en' => 'Flash Floods & Rain', 'rain_mm' => 180, 'temp' => '25° / 23°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'টানা বর্ষণ', 'condition_en' => 'Continuous Rain', 'rain_mm' => 110, 'temp' => '26° / 23°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'বজ্রবৃষ্টি', 'condition_en' => 'Thunderstorms', 'rain_mm' => 50, 'temp' => '27° / 24°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'মেঘালয় থেকে উজান পাহাড়ি ঢল অব্যাহত। হাওর অববাহিকার সড়ক যোগাযোগ বিচ্ছিন্ন। নৌকায় চলাচলের সময় লাইফ জ্যাকেট ব্যবহার করুন এবং জরুরি শুকনো খাবার সংরক্ষণ করুন।',
            'advisory_en'       => 'Severe flash flood runoff continues from upstream Meghalaya hills. Haor road links submerged. Use life jackets during boat transit and secure dry food stocks.',
            'shelters'          => [
                ['name_bn' => 'সুনামগঞ্জ জুবিলী সরকারি উচ্চ বিদ্যালয়', 'name_en' => 'Sunamganj Jubilee Govt High School', 'capacity' => 800, 'contact' => '01712-345601'],
                ['name_bn' => 'তাহিরপুর বালিকা উচ্চ বিদ্যালয়', 'name_en' => 'Tahirpur Girls High School', 'capacity' => 400, 'contact' => '01712-345602']
            ]
        ],
        'sylhet' => [
            'station_id'        => 'BD-FFWC-SYL',
            'station_name_bn'   => 'সিলেট কোম্পানীগঞ্জ ও কুশিয়ারা পয়েন্ট',
            'station_name_en'   => 'Sylhet Companiganj & Kushiyara Point',
            'district_bn'       => 'সিলেট',
            'district_en'       => 'Sylhet',
            'division_bn'       => 'সিলেট',
            'division_en'       => 'Sylhet',
            'river_bn'          => 'কুশিয়ারা নদী',
            'river_en'          => 'Kushiyara River',
            'lat'               => 25.0815,
            'lon'               => 91.7523,
            'water_level_m'     => 12.85,
            'danger_level_m'    => 12.50,
            'delta_m'           => 0.35,
            'river_trend'       => 'rising',
            'alert_level'       => 'WARNING',
            'alert_color'       => '#FBBF24',
            'weather'           => [
                'condition_bn'    => 'দমকা হাওয়াসহ ভারী বৃষ্টিপাত',
                'condition_en'    => 'Heavy Rain with Gusty Wind',
                'temp_c'          => 27.0,
                'feels_like_c'    => 32.0,
                'rainfall_24h_mm' => 92.5,
                'rainfall_72h_mm' => 195.0,
                'humidity_pct'    => 92,
                'wind_kmh'        => 28,
                'wind_dir'        => 'NE (উত্তর-পূর্ব)',
                'pressure_hpa'    => 1004,
                'visibility_km'   => 4.5
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'ভারী বৃষ্টিপাত', 'condition_en' => 'Heavy Showers', 'rain_mm' => 92, 'temp' => '28° / 24°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'বজ্রবৃষ্টি', 'condition_en' => 'Thunderstorms', 'rain_mm' => 60, 'temp' => '29° / 25°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'বিক্ষিপ্ত বৃষ্টি', 'condition_en' => 'Scattered Rain', 'rain_mm' => 35, 'temp' => '30° / 25°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'কুশিয়ারা নদীর পানি বিপদসীমার ৩৫ সেমি উপরে। নিচু এলাকার বাড়িঘরে পানি প্রবেশ করতে পারে। বিশুদ্ধকরণ ট্যাবলেট দিয়ে পানি ফুটিয়ে পান করুন।',
            'advisory_en'       => 'Kushiyara river is 35cm above danger line. Lowlands risk inundation. Boil drinking water or use water purification tablets.',
            'shelters'          => [
                ['name_bn' => 'কোম্পানীগঞ্জ থানা সদর প্রাথমিক বিদ্যালয়', 'name_en' => 'Companiganj Model Primary School', 'capacity' => 500, 'contact' => '01713-456701'],
                ['name_bn' => 'গোয়াইনঘাট ডিগ্রি কলেজ ভবন', 'name_en' => 'Gowainghat Degree College Shelter', 'capacity' => 700, 'contact' => '01713-456702']
            ]
        ],
        'jamalpur' => [
            'station_id'        => 'BD-FFWC-JML',
            'station_name_bn'   => 'জামালপুর বাহাদুরাবাদ ও যমুনা পয়েন্ট',
            'station_name_en'   => 'Jamalpur Bahadurabad & Jamuna Point',
            'district_bn'       => 'জামালপুর',
            'district_en'       => 'Jamalpur',
            'division_bn'       => 'ময়মনসিংহ',
            'division_en'       => 'Mymensingh',
            'river_bn'          => 'যমুনা নদী',
            'river_en'          => 'Jamuna River',
            'lat'               => 25.0833,
            'lon'               => 89.7833,
            'water_level_m'     => 19.95,
            'danger_level_m'    => 19.50,
            'delta_m'           => 0.45,
            'river_trend'       => 'rising',
            'alert_level'       => 'WARNING',
            'alert_color'       => '#FBBF24',
            'weather'           => [
                'condition_bn'    => 'মেঘলা আকাশ ও মধ্যম থেকে ভারী বৃষ্টি',
                'condition_en'    => 'Overcast & Moderate to Heavy Rain',
                'temp_c'          => 28.0,
                'feels_like_c'    => 33.0,
                'rainfall_24h_mm' => 78.0,
                'rainfall_72h_mm' => 160.0,
                'humidity_pct'    => 90,
                'wind_kmh'        => 24,
                'wind_dir'        => 'S (দক্ষিণ)',
                'pressure_hpa'    => 1005,
                'visibility_km'   => 5.0
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'মধ্যম বৃষ্টি', 'condition_en' => 'Moderate Rain', 'rain_mm' => 78, 'temp' => '28° / 25°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'বজ্রবৃষ্টি', 'condition_en' => 'Thunderstorms', 'rain_mm' => 55, 'temp' => '29° / 25°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'হালকা বৃষ্টি', 'condition_en' => 'Light Showers', 'rain_mm' => 25, 'temp' => '30° / 26°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'বাহাদুরাবাদ ঘাটে যমুনার পানি বৃদ্ধি পাচ্ছে। ইসলামপুর ও দেওয়ানগঞ্জের নিম্নাঞ্চল প্লাবিত হওয়ার ঝুঁকি রয়েছে। বাঁধের ওপর নজরদারি জোরদার করা হয়েছে।',
            'advisory_en'       => 'Jamuna river stage swelling at Bahadurabad. Lowlands of Islampur and Dewanganj under inundation alert. Embankment monitoring active.',
            'shelters'          => [
                ['name_bn' => 'ইসলামপুর নেকজাহান সরকারি মডেল হাইস্কুল', 'name_en' => 'Islampur Nekjahan Model High School', 'capacity' => 650, 'contact' => '01714-567801']
            ]
        ],
        'gaibandha' => [
            'station_id'        => 'BD-FFWC-GAI',
            'station_name_bn'   => 'গাইবান্ধা সুন্দরগঞ্জ ও ঘাঘট পয়েন্ট',
            'station_name_en'   => 'Gaibandha Sundarganj & Ghaghat Point',
            'district_bn'       => 'গাইবান্ধা',
            'district_en'       => 'Gaibandha',
            'division_bn'       => 'রংপুর',
            'division_en'       => 'Rangpur',
            'river_bn'          => 'ঘাঘট ও তিস্তা নদী',
            'river_en'          => 'Ghaghat & Teesta Rivers',
            'lat'               => 25.3297,
            'lon'               => 89.5430,
            'water_level_m'     => 21.60,
            'danger_level_m'    => 21.70,
            'delta_m'           => -0.10,
            'river_trend'       => 'steady',
            'alert_level'       => 'ADVISORY',
            'alert_color'       => '#38BDF8',
            'weather'           => [
                'condition_bn'    => 'বিক্ষিপ্ত বৃষ্টি ও আর্দ্র আবহাওয়া',
                'condition_en'    => 'Scattered Rain & Humid',
                'temp_c'          => 28.5,
                'feels_like_c'    => 33.5,
                'rainfall_24h_mm' => 52.0,
                'rainfall_72h_mm' => 110.0,
                'humidity_pct'    => 88,
                'wind_kmh'        => 20,
                'wind_dir'        => 'SE (দক্ষিণ-পূর্ব)',
                'pressure_hpa'    => 1006,
                'visibility_km'   => 6.0
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'বিক্ষিপ্ত বৃষ্টি', 'condition_en' => 'Scattered Rain', 'rain_mm' => 52, 'temp' => '29° / 25°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'মেঘলা আকাশ', 'condition_en' => 'Mostly Cloudy', 'rain_mm' => 30, 'temp' => '30° / 26°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'হালকা রোদ-বৃষ্টি', 'condition_en' => 'Passing Showers', 'rain_mm' => 15, 'temp' => '31° / 26°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'পানি বিপদসীমার ১০ সেমি নিচে অবস্থান করছে। তবে উজানে বৃষ্টি বাড়লে চরাঞ্চলে পানি উঠতে পারে। সতর্কতা অবলম্বন করুন।',
            'advisory_en'       => 'Water level is 10cm below danger mark. Upstream runoff may push levels upward. Stay alert.',
            'shelters'          => [
                ['name_bn' => 'সুন্দরগঞ্জ ডি ডব্লিউ সরকারি কলেজ', 'name_en' => 'Sundarganj DW Govt College', 'capacity' => 550, 'contact' => '01715-678901']
            ]
        ],
        'feni' => [
            'station_id'        => 'BD-FFWC-FEN',
            'station_name_bn'   => 'ফেনী পরশুরাম ও মুহুরী নদী পয়েন্ট',
            'station_name_en'   => 'Feni Parshuram & Muhuri River Point',
            'district_bn'       => 'ফেনী',
            'district_en'       => 'Feni',
            'division_bn'       => 'চট্টগ্রাম',
            'division_en'       => 'Chittagong',
            'river_bn'          => 'মুহুরী, কহুয়া ও সিলোনিয়া নদী',
            'river_en'          => 'Muhuri, Kahua & Silonia Rivers',
            'lat'               => 23.0186,
            'lon'               => 91.3966,
            'water_level_m'     => 13.80,
            'danger_level_m'    => 13.00,
            'delta_m'           => 0.80,
            'river_trend'       => 'rising',
            'alert_level'       => 'CRITICAL',
            'alert_color'       => '#FF6B6B',
            'weather'           => [
                'condition_bn'    => 'উজান পাহাড়ি ঢল ও অতি ভারী বর্ষণ',
                'condition_en'    => 'Upstream Hill Torrent & Intense Rain',
                'temp_c'          => 26.0,
                'feels_like_c'    => 30.5,
                'rainfall_24h_mm' => 165.0,
                'rainfall_72h_mm' => 290.0,
                'humidity_pct'    => 95,
                'wind_kmh'        => 35,
                'wind_dir'        => 'S (দক্ষিণ)',
                'pressure_hpa'    => 1001,
                'visibility_km'   => 3.0
            ],
            'forecast_3days'    => [
                ['day' => 'Today', 'day_bn' => 'আজ', 'condition_bn' => 'অতি ভারী বৃষ্টি', 'condition_en' => 'Heavy Torrential Rain', 'rain_mm' => 165, 'temp' => '26° / 23°', 'icon' => 'cloud-rain'],
                ['day' => 'Tomorrow', 'day_bn' => 'আগামীকাল', 'condition_bn' => 'টানা বৃষ্টি', 'condition_en' => 'Continuous Rain', 'rain_mm' => 90, 'temp' => '27° / 24°', 'icon' => 'cloud-rain'],
                ['day' => 'Day 3', 'day_bn' => 'পরশু', 'condition_bn' => 'বজ্রঝড়', 'condition_en' => 'Thunderstorm', 'rain_mm' => 35, 'temp' => '29° / 25°', 'icon' => 'cloud-rain']
            ],
            'advisory_bn'       => 'মুহুরী নদীর বাঁধে ফাটল ও পানি বিপদসীমার ৮০ সেমি উপরে। পরশুরাম ও ফুলগাজীর বাসিন্দারা অবিলম্বে বিদ্যুৎ সংযোগ বন্ধ করে আশ্রয়কেন্দ্রে যান।',
            'advisory_en'       => 'Muhuri river 80cm above danger level with embankment breach risks. Disconnect power mains and evacuate to safe centers.',
            'shelters'          => [
                ['name_bn' => 'পরশুরাম সরকারি পাইলট হাই স্কুল', 'name_en' => 'Parshuram Govt Pilot High School', 'capacity' => 600, 'contact' => '01716-789012'],
                ['name_bn' => 'ফুলগাজী বালিকা পাইলট বিদ্যালয়', 'name_en' => 'Fulgazi Girls Pilot School', 'capacity' => 500, 'contact' => '01716-789013']
            ]
        ]
    ];
}

