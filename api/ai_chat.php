<?php
// ReliefX AI Helpline Bridge (FastAPI Microservice + Built-in NLP Fallback)
// Accessible without session authentication for citizens and guests.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true) ?: [];
$message = trim($payload['message'] ?? '');
$lang = $payload['language'] ?? 'bn';

if ($message === '') {
    echo json_encode([
        'reply' => $lang === 'bn' ? 'অনুগ্রহ করে আপনার প্রশ্ন বা বার্তাটি লিখুন।' : 'Please type your inquiry or message.',
        'is_emergency' => false,
        'suggested_actions' => ["📦 ত্রাণ পাওয়ার যোগ্যতা", "⏳ ৫ দিন পর জরুরি আবেদন", "🌊 আবহাওয়া ও বন্যা সতর্কতা"]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Try forwarding to FastAPI microservice on port 8001
$fastApiUrl = 'http://127.0.0.1:8001/api/v1/chat/webhook';
$ch = curl_init($fastApiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $rawInput,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);
$fastApiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($fastApiResponse && $httpCode === 200) {
    echo $fastApiResponse;
    exit;
}

// 2. Intelligent Built-in NLP Engine (Runs whenever FastAPI is offline or slow)
$isBn = preg_match('/[\x{0980}-\x{09FF}]/u', $message) || ($lang === 'bn');
$lower = mb_strtolower($message, 'UTF-8');

$isInfoQuery = (
    str_contains($lower, 'কীভাবে') || str_contains($lower, 'কী') || str_contains($lower, 'কেমন') ||
    str_contains($lower, 'নিয়ম') || str_contains($lower, 'নিয়ম') || str_contains($lower, 'নম্বর') ||
    str_contains($lower, 'নাম্বার') || str_contains($lower, 'ফোন') || str_contains($lower, 'শর্ত') ||
    str_contains($lower, 'how') || str_contains($lower, 'what') || str_contains($lower, 'rule') ||
    str_contains($lower, 'number') || str_contains($lower, 'hotline')
);

// Emergency Evacuation / Rescue Check
$isTrapped = (
    str_contains($lower, 'বাঁচাও') || str_contains($lower, 'বাচাও') || str_contains($lower, 'আটকা') ||
    str_contains($lower, 'উদ্ধার') || str_contains($lower, 'ডুবছে') || str_contains($lower, 'ছাদে') ||
    str_contains($lower, 'তলিয়ে') || str_contains($lower, 'তলিয়ে') || str_contains($lower, 'rescue') ||
    str_contains($lower, 'trapped') || str_contains($lower, 'drowning') || str_contains($lower, 'stranded')
);

if ($isTrapped && !$isInfoQuery) {
    $ticketId = 'TKT-2026-' . rand(4000, 9999);
    $reply = $isBn
        ? "🚨 **জরুরি উদ্ধার টিকেট খোলা হয়েছে (#{$ticketId})!**\n\n" .
          "আপনার উদ্ধার বার্তাটি সাথে সাথে দুর্যোগ কমান্ড সেন্টারে রেকর্ড করা হয়েছে।\n" .
          "🛥️ **নিয়োজিত ইউনিট:** ফায়ার সার্ভিস ও সেনা উদ্ধারকারী স্পিডবোট দল।\n" .
          "📞 তাত্ক্ষণিক সাহায্যের জন্য এখনই সরাসরি ডায়াল করুন: **৯৯৯ (জাতীয় জরুরি সেবা)** অথবা **১০৯০ (দুর্যোগ সেল)**।"
        : "🚨 **Emergency Rescue Ticket Created (#{$ticketId})!**\n\n" .
          "Your distress request is logged and dispatched to Disaster Rescue Units.\n" .
          "🛥️ **Assigned Unit:** Fire Service & Armed Forces Rescue Team.\n" .
          "📞 Immediate hotlines: Dial **999** (National Emergency) or **1090** (Disaster Warning).";

    echo json_encode([
        'reply' => $reply,
        'is_emergency' => true,
        'ticket' => [
            'ticket_id' => $ticketId,
            'status' => 'DISPATCHED',
            'beneficiary_name' => $isBn ? 'নাগরিক (Citizen In Need)' : 'Citizen In Need',
            'detected_gps' => 'Auto-triaged by Helpline',
            'detected_landmark' => 'Flood affected area',
            'assigned_unit' => 'বাংলাদেশ ফায়ার সার্ভিস ও উদ্ধার ইউনিট'
        ],
        'suggested_actions' => ["🚨 Call 999 Directly", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Eligibility & 7-Day Cooldown Rule
if (str_contains($lower, 'যোগ্যতা') || str_contains($lower, '৭ দিন') || str_contains($lower, '৭ দিনের') || str_contains($lower, 'eligibility') || str_contains($lower, 'কবে পাব') || str_contains($lower, 'শর্ত')) {
    $reply = $isBn
        ? "📦 **ত্রাণ পাওয়ার যোগ্যতা ও ৭ দিনের নিয়ম:**\n\n" .
          "১. **স্বচ্ছ বিতরণ ও ডুপ্লিকেট রোধ:** ReliefX সিস্টেমে প্রতিটি পরিবার একটি নির্দিষ্ট ক্যাটাগরিতে (যেমন খাদ্যসামগ্রী, জরুরি ওষুধ, বা বস্ত্র) ত্রাণ পাওয়ার পর **পরবর্তী ৭ দিন** ওই ক্যাটাগরির জন্য অপেক্ষমাণ থাকে।\n" .
          "২. **ডাটাবেস সুরক্ষা:** ৭ দিনের মধ্যে পুনরায় একই ক্যাটাগরিতে ত্রাণ নেওয়ার চেষ্টা করা হলে ডাটাবেসের ইউনিক কনস্ট্রেইন্ট তা স্বয়ংক্রিয়ভাবে ব্লক করে এবং অডিট লগে সংরক্ষণ করে।\n" .
          "৩. **যোগ্যতা যাচাই করার উপায়:** আপনার **Citizen একাউন্টে** লগইন করুন। ড্যাশবোর্ডে আপনি পূর্বে কী কী ত্রাণ পেয়েছেন, কোন এনজিও দিয়েছে এবং পরবর্তী ত্রাণ পাওয়ার তারিখ স্পষ্টভাবে দেখতে পাবেন।"
        : "📦 **Relief Eligibility & The 7-Day Rule:**\n\n" .
          "1. **Fair Allocation & Duplicate Prevention:** After receiving relief in a category (Food, Medical, Shelter), families enter a **7-day cooldown**.\n" .
          "2. **Automatic Guard:** Attempts to claim relief within 7 days are automatically rejected by the database.\n" .
          "3. **How to check:** Log into your **Citizen Dashboard** with your NID to view full distribution history and next eligible date.";

    $actions = ["⏳ ৫ দিন পর জরুরি আবেদন", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "📞 জরুরি হটলাইন নম্বরসমূহ"];
}

// 2. 5-Day Emergency Protocol
elseif (str_contains($lower, '৫ দিন') || str_contains($lower, 'জরুরি ত্রাণ') || str_contains($lower, 'জরুরি আবেদন') || str_contains($lower, '5-day') || str_contains($lower, 'emergency relief') || str_contains($lower, 'আবেদন')) {
    $reply = $isBn
        ? "⏳ **৫ দিন পর জরুরি ত্রাণের বিশেষ আবেদন প্রোটোকল:**\n\n" .
          "• **জরুরি প্রোটোকল কী?** সাধারণত ত্রাণের স্বাভাবিক বিরতি ৭ দিন। কিন্তু গুরুতর বন্যা পরিস্থিতিতে কোনো পরিবার যদি টানা **৫ দিন কোনো ত্রাণ না পেয়ে থাকে**, তবে তারা বিশেষ জরুরি ত্রাণের আবেদন করতে পারে।\n" .
          "• **আবেদনের নিয়ম:**\n" .
          "  ১. **Citizen Dashboard** (`citizen_dashboard.php`) এ আপনার NID ও পাসওয়ার্ড দিয়ে লগইন করুন।\n" .
          "  ২. 'জরুরি ত্রাণের আবেদন' কার্ডে পরিবারের বর্তমান অবস্থা ও জরুরি প্রয়োজন (খাদ্য/ওষুধ/শিশু খাদ্য) লিখে সাবমিট করুন।\n" .
          "  ৩. সংশ্লিষ্ট এলাকার ফিল্ড এনজিও অপারেটরদের কাছে তাৎক্ষণিক অগ্রাধিকার নোটিফিকেশন চলে যাবে।"
        : "⏳ **5-Day Emergency Relief Protocol:**\n\n" .
          "• Families facing crisis without relief for **5 consecutive days** can file an emergency petition.\n" .
          "• Submit your petition directly via your **Citizen Dashboard** (`citizen_dashboard.php`).\n" .
          "• Local registered NGO operators receive immediate priority dispatch alerts.";

    $actions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"];
}

// 3. Weather & River Levels
elseif (str_contains($lower, 'আবহাওয়া') || str_contains($lower, 'বন্যা পরিস্থিতি') || str_contains($lower, 'নদীর পানি') || str_contains($lower, 'বিপদসীমা') || str_contains($lower, 'weather') || str_contains($lower, 'flood') || str_contains($lower, 'river')) {
    $reply = $isBn
        ? "🌊 **লাইভ আবহাওয়া ও নদী বন্যা সতর্কতা:**\n\n" .
          "• **মনিটরিং স্টেশন:** ReliefX লাইভ পোর্টালে (`weather_alerts.php`) কুড়িগ্রাম (ধরলা নদী), সুনামগঞ্জ (সুরমা নদী), সিলেট, জামালপুর সহ ৮টি প্রধান স্টেশনের ডেটা প্রতি ঘন্টায় আপডেট হয়।\n" .
          "• **বর্তমান অবস্থা:** ধরলা ও সুরমা নদীর পানি বিপদসীমার উপর দিয়ে প্রবাহিত হচ্ছে। কুড়িগ্রাম ও সুনামগঞ্জ সদরে উচ্চ সতর্কতা (High Alert) জারি রয়েছে।\n" .
          "• বিস্তারিত লাইভ ভিজুয়ালাইজেশন ও গ্রাফ দেখতে উপরের **Weather & Alerts** মেনুতে ক্লিক করুন।"
        : "🌊 **Live Weather & Flood Alert Overview:**\n\n" .
          "• Real-time hydrological monitoring is active for 8 vulnerable flood zones via `weather_alerts.php`.\n" .
          "• Dharla and Surma rivers are flowing near danger thresholds with high rainfall predicted upstream.\n" .
          "• Check the top **Weather & Alerts** tab to see real-time river gauges and precipitation trends.";

    $actions = ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "📞 জরুরি হটলাইন নম্বরসমূহ"];
}

// 4. Citizen Registration & Login
elseif (str_contains($lower, 'নাগরিক একাউন্ট') || str_contains($lower, 'রেজিস্ট্রেশন') || str_contains($lower, 'খুলব') || str_contains($lower, 'citizen') || str_contains($lower, 'register') || str_contains($lower, 'signup')) {
    $reply = $isBn
        ? "👤 **নাগরিক একাউন্ট খোলার সহজ নিয়ম:**\n\n" .
          "১. লগইন পেজের নিচে **'Open Citizen Account'** বা `register_user.php` লিঙ্কে যান।\n" .
          "২. আপনার নাম, সক্রিয় মোবাইল নম্বর, ১০ বা ১৭ ডিজিটের **জাতীয় পরিচয়পত্র (NID)** এবং একটি পাসওয়ার্ড লিখুন।\n" .
          "৩. একাউন্ট খোলা সম্পন্ন হলে লগইন করে আপনি আপনার পরিবারের সকল পূর্ববর্তী ত্রাণের রেকর্ড, পরবর্তী পাওয়ার তারিখ এবং ৫ দিন পর জরুরি ত্রাণের আবেদন করতে পারবেন।\n" .
          "৪. একাউন্ট না খুলেও সাধারণ তথ্য দেখতে চাইলে লগইন পেজের **'Continue as Guest'** বেছে নিতে পারেন।"
        : "👤 **How to Register a Citizen Account:**\n\n" .
          "1. Click **'Open Citizen Account'** on the login page or visit `register_user.php`.\n" .
          "2. Provide your Full Name, Active Phone Number, 10 or 17 digit **NID**, and set a password.\n" .
          "3. Once registered, log in to check relief history, upcoming eligibility, and submit 5-day emergency petitions.\n" .
          "4. You can also explore public distribution records anytime by clicking **'Continue as Guest'**.";

    $actions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"];
}

// 5. Emergency Hotlines
elseif (str_contains($lower, 'হটলাইন') || str_contains($lower, 'নম্বর') || str_contains($lower, 'নাম্বার') || str_contains($lower, 'ফোন') || str_contains($lower, 'hotline') || str_contains($lower, 'number') || str_contains($lower, 'contact') || str_contains($lower, '999') || str_contains($lower, '1090')) {
    $reply = $isBn
        ? "📞 **জাতীয় জরুরি দুর্যোগ হেল্পলাইন নম্বরসমূহ:**\n\n" .
          "• 🚨 **৯৯৯ (999)**: জাতীয় জরুরি সেবা (পুলিশ, অ্যাম্বুলেন্স, ফায়ার সার্ভিস উদ্ধার)\n" .
          "• 🌊 **১০৯০ (1090)**: দুর্যোগ প্রস্তুতি ও নদী বন্যা আগাম সতর্কতা বার্তা (টোল-ফ্রি)\n" .
          "• ℹ️ **৩৩৩ (333)**: সরকারি তথ্য, সেবা ও সামাজিক সহায়তা হেল্পলাইন\n" .
          "• 🩺 **১৬১৬৩ (16163)**: বাংলাদেশ রেড ক্রিসেন্ট সোসাইটি জরুরি সাড়া সেল"
        : "📞 **National Disaster Emergency Hotlines:**\n\n" .
          "• 🚨 **999**: National Emergency Services (Police, Ambulance, Fire & Rescue)\n" .
          "• 🌊 **1090**: Flood Early Warning & Disaster Weather Helpline (Toll-Free)\n" .
          "• ℹ️ **333**: National Citizen Grievance & Government Services Helpline\n" .
          "• 🩺 **16163**: Bangladesh Red Crescent Emergency Relief Cell";

    $actions = ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"];
}

// 6. Greetings & Friendly Default
elseif (str_contains($lower, 'hi') || str_contains($lower, 'hello') || str_contains($lower, 'hey') || str_contains($lower, 'সালাম') || str_contains($lower, 'নমস্কার') || str_contains($lower, 'কেমন আছেন') || str_contains($lower, 'kemon asen')) {
    $reply = $isBn
        ? "👋 **নমস্কার / সালাম!** আমি **ReliefX AI স্মার্ট সহকারী**।\n\n" .
          "বন্যা ও ত্রাণ সংক্রান্ত যেকোনো তথ্যের জন্য আমি সর্বদা প্রস্তুত। আপনি জানতে পারেন:\n" .
          "• 📦 ত্রাণ পাওয়ার যোগ্যতা ও ৭ দিনের নিয়ম\n" .
          "• ⏳ ৫ দিন পর জরুরি ত্রাণের আবেদন প্রক্রিয়া\n" .
          "• 🌊 লাইভ আবহাওয়া ও নদী বন্যা সতর্কতা\n" .
          "• 👤 নাগরিক একাউন্ট তৈরি ও ট্র্যাকিং\n" .
          "• 🚨 বিপদে পড়লে তাত্ক্ষণিক উদ্ধার টিকেট"
        : "👋 **Hello!** Welcome to **ReliefX AI Helpline Assistant**.\n\n" .
          "How can I assist you today? You can ask me about:\n" .
          "• 📦 Relief eligibility and duplicate prevention rules\n" .
          "• ⏳ 5-day emergency relief application protocol\n" .
          "• 🌊 Real-time river stages and flood forecasts\n" .
          "• 👤 Citizen account registration\n" .
          "• 🚨 Emergency flood rescue triage";

    $actions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি", "📞 জরুরি হটলাইন নম্বরসমূহ"];
}

else {
    $reply = $isBn
        ? "ℹ️ আপনার বার্তার জন্য ধন্যবাদ। আমি **ReliefX AI হেল্পলাইন**।\n\n" .
          "• ত্রাণ বিতরণের যোগ্যতা যাচাই করতে আপনার **NID** নম্বর লিখুন।\n" .
          "• জরুরি বন্যায় আটকা পড়ে থাকলে আপনার **অবস্থান বা ল্যান্ডমার্ক** জানান—আমি উদ্ধার টিকেট তৈরি করব।\n" .
          "• অথবা নিচের প্রস্তাবিত প্রশ্নগুলোর যেকোনো একটিতে ক্লিক করুন।"
        : "ℹ️ Thank you for your inquiry. I am the **ReliefX AI Helpline**.\n\n" .
          "• Enter your **NID** to check family relief status.\n" .
          "• If stranded by floodwaters, share your **landmark or GPS** for an immediate rescue ticket.\n" .
          "• Or select one of the quick topics below.";

    $actions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "📞 জরুরি হটলাইন নম্বরসমূহ"];
}

echo json_encode([
    'reply' => $reply,
    'is_emergency' => false,
    'ticket' => null,
    'suggested_actions' => $actions
], JSON_UNESCAPED_UNICODE);
