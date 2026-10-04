<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $reviews = [
            'Aarav Sen' => [
                'priority' => 'routine',
                'summary' => 'Current information supports continuing routine observation and strengths-based learning. / বর্তমান তথ্য অনুযায়ী নিয়মিত পর্যবেক্ষণ ও শক্তিভিত্তিক শেখা চালিয়ে যাওয়া উপযোগী।',
            ],
            'Nusrat Jahan' => [
                'priority' => 'follow_up',
                'summary' => 'A short adult follow-up may help connect classroom stamina and current routines. / সংক্ষিপ্ত প্রাপ্তবয়স্ক ফলো-আপ শ্রেণিকক্ষের স্থায়িত্ব ও বর্তমান রুটিনের সম্পর্ক বুঝতে সাহায্য করতে পারে।',
                'evidence' => ['Participation is strong in collaborative work but less consistent during extended writing. / দলীয় কাজে অংশগ্রহণ ভালো, তবে দীর্ঘ লেখার সময় ধারাবাহিকতা কম।'],
                'teacher_actions' => ['Use shorter writing stages and check progress after each stage. / লেখার কাজ ছোট ধাপে ভাগ করুন এবং প্রতিটি ধাপের পর অগ্রগতি দেখুন।'],
                'parent_actions' => ['Keep the evening learning routine short and predictable. / সন্ধ্যার শেখার রুটিন সংক্ষিপ্ত ও পূর্বানুমেয় রাখুন।'],
            ],
            'Rafi Islam' => [
                'priority' => 'priority',
                'summary' => 'Recent teacher and family observations suggest a timely human review would be useful. / সাম্প্রতিক শিক্ষক ও পারিবারিক পর্যবেক্ষণ অনুযায়ী দ্রুত মানবিক পর্যালোচনা উপকারী হতে পারে।',
                'evidence' => ['Reduced classroom participation across two weeks / দুই সপ্তাহ ধরে শ্রেণিকক্ষে অংশগ্রহণ কমেছে', 'Family reports a recent change in sleep routine / পরিবার ঘুমের রুটিনে সাম্প্রতিক পরিবর্তনের কথা জানিয়েছে'],
                'teacher_actions' => ['Arrange a private check-in with the school counsellor. / স্কুল কাউন্সেলরের সঙ্গে ব্যক্তিগত আলোচনার ব্যবস্থা করুন।', 'Keep academic tasks predictable while more context is gathered. / আরও তথ্য সংগ্রহের সময় শিক্ষার কাজগুলো পূর্বানুমেয় রাখুন।'],
                'parent_actions' => ['Share recent changes with the school support contact. / সাম্প্রতিক পরিবর্তন স্কুলের সহায়তা প্রতিনিধিকে জানান।'],
            ],
            'Anika Das' => ['priority' => 'context_needed'],
        ];

        foreach ($reviews as $name => $review) {
            $profile = DB::table('profiles')->where('type', 'child')->where('full_name', $name)->first();
            if (! $profile) {
                continue;
            }

            $details = json_decode($profile->details ?: '{}', true) ?: [];
            $details['support_review'] = $review;
            DB::table('profiles')->where('id', $profile->id)->update([
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('profiles')->where('type', 'child')->whereIn('full_name', ['Aarav Sen', 'Nusrat Jahan', 'Rafi Islam', 'Anika Das'])
            ->get()->each(function ($profile) {
                $details = json_decode($profile->details ?: '{}', true) ?: [];
                unset($details['support_review']);
                DB::table('profiles')->where('id', $profile->id)->update([
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            });
    }
};
