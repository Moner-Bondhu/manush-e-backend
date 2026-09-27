<?php

namespace Database\Seeders;

use App\Models\Scale;
use Illuminate\Database\Seeder;

class MeasurementLibrarySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->packs() as $pack) {
            $questions = $pack['questions'];
            unset($pack['questions']);
            $scale = Scale::updateOrCreate(['code' => $pack['code']], $pack);

            foreach ($questions as $index => $item) {
                $question = $scale->questions()->updateOrCreate(
                    ['order' => $index + 1],
                    [
                        'text' => $item['text'],
                        'subtext' => $item['subtext'] ?? null,
                        'type' => $item['type'] ?? 'select_one',
                        'construct' => $item['construct'],
                        'reverse_scored' => $item['reverse_scored'] ?? false,
                        'image' => null,
                    ]
                );
                foreach ($this->optionsFor($item['options'] ?? 'frequency') as $optionIndex => $option) {
                    $question->options()->updateOrCreate(
                        ['order' => $optionIndex + 1],
                        ['text' => $option[0], 'value' => $option[1], 'is_image' => false]
                    );
                }
            }
        }
    }

    private function optionsFor(string $type): array
    {
        return match ($type) {
            'ease' => [['Very hard / খুব কঠিন', 1], ['Hard / কঠিন', 2], ['Okay / মোটামুটি', 3], ['Easy / সহজ', 4], ['Very easy / খুব সহজ', 5]],
            'agreement' => [['Not like me / আমার মতো নয়', 1], ['A little like me / কিছুটা আমার মতো', 2], ['Mostly like me / বেশিরভাগ আমার মতো', 3], ['Very much like me / পুরোপুরি আমার মতো', 4]],
            'frequency_adult' => [['Not observed / দেখা যায়নি', 1], ['Sometimes / কখনও কখনও', 2], ['Often / প্রায়ই', 3], ['Consistently / নিয়মিত', 4]],
            default => [['Never / কখনও না', 1], ['Sometimes / কখনও কখনও', 2], ['Often / প্রায়ই', 3], ['Almost always / প্রায় সবসময়', 4]],
        };
    }

    private function packs(): array
    {
        $base = [
            'evidence_type' => 'structured_check_in',
            'cadence' => 'monthly',
            'estimated_minutes' => 4,
            'minimum_age' => 8,
            'maximum_age' => 17,
            'instrument_status' => 'original_research_informed',
            'source_name' => 'Manush-E Core Measurement Library v0.1',
            'interpretation_note' => 'One source of evidence. Review alongside game, school, family, and longitudinal context.',
            'is_published' => true,
        ];

        return [
            array_merge($base, [
                'code' => 'CHILD-WELLBEING-PULSE', 'name' => 'Wellbeing Pulse / ভালো থাকার খোঁজ', 'visible_to' => 'child',
                'domain' => 'mental_wellbeing', 'constructs' => ['mood', 'stress', 'belonging', 'support', 'hope'],
                'description' => 'A short check-in about feelings, pressure, connection, and available support. / অনুভূতি, চাপ, সম্পর্ক ও পাওয়া যায় এমন সহায়তা নিয়ে ছোট একটি খোঁজ।',
                'questions' => [
                    ['text' => 'I felt calm enough to do the things I needed to do. / প্রয়োজনীয় কাজ করার মতো শান্ত অনুভব করেছি।', 'construct' => 'emotional_regulation'],
                    ['text' => 'I felt that I belonged at school. / স্কুলে নিজেকে আপন মনে হয়েছে।', 'construct' => 'school_belonging'],
                    ['text' => 'Worries made it hard to focus. / দুশ্চিন্তার কারণে মনোযোগ দিতে কষ্ট হয়েছে।', 'construct' => 'stress_load', 'reverse_scored' => true],
                    ['text' => 'I had someone I could talk to when I needed help. / সাহায্য দরকার হলে কথা বলার মতো কেউ ছিল।', 'construct' => 'support_access'],
                    ['text' => 'I could imagine something good ahead. / সামনে ভালো কিছু হবে বলে ভাবতে পেরেছি।', 'construct' => 'hope'],
                ],
            ]),
            array_merge($base, [
                'code' => 'CHILD-LEARNING-STRENGTHS', 'name' => 'Learning Strengths / শেখার শক্তি', 'visible_to' => 'child',
                'domain' => 'pedagogy', 'constructs' => ['visual', 'verbal', 'hands_on', 'collaborative', 'self_directed'],
                'description' => 'How a learner currently understands, expresses, and practices new ideas. / একজন শিক্ষার্থী এখন কীভাবে নতুন ধারণা বোঝে, প্রকাশ করে ও অনুশীলন করে।',
                'questions' => [
                    ['text' => 'Pictures, maps, or diagrams help me understand a new idea. / ছবি, মানচিত্র বা চিত্র আমাকে নতুন ধারণা বুঝতে সাহায্য করে।', 'construct' => 'visual_expression', 'options' => 'agreement'],
                    ['text' => 'Talking through an idea helps me learn. / একটি ধারণা নিয়ে কথা বললে আমার শিখতে সুবিধা হয়।', 'construct' => 'verbal_expression', 'options' => 'agreement'],
                    ['text' => 'I learn well when I can build, move, or try something. / বানিয়ে, নড়ে বা চেষ্টা করে আমি ভালো শিখি।', 'construct' => 'hands_on_learning', 'options' => 'agreement'],
                    ['text' => 'Working with another student helps me improve my thinking. / অন্য শিক্ষার্থীর সঙ্গে কাজ করলে আমার ভাবনা উন্নত হয়।', 'construct' => 'collaborative_learning', 'options' => 'agreement'],
                    ['text' => 'I can choose a useful way to begin a new task. / নতুন কাজ শুরু করার উপযোগী উপায় বেছে নিতে পারি।', 'construct' => 'learning_agency', 'options' => 'agreement'],
                ],
            ]),
            array_merge($base, [
                'code' => 'CHILD-EXECUTIVE-SKILLS', 'name' => 'Everyday Thinking Skills / দৈনন্দিন চিন্তার দক্ষতা', 'visible_to' => 'child',
                'domain' => 'psychometric', 'constructs' => ['attention', 'working_memory', 'planning', 'flexibility', 'inhibition'],
                'description' => 'A child-friendly reflection on planning, remembering, shifting, and staying with a task. / পরিকল্পনা, মনে রাখা, কৌশল বদলানো ও কাজে লেগে থাকা নিয়ে শিশুবান্ধব প্রতিফলন।',
                'questions' => [
                    ['text' => 'I can return to a task after something distracts me. / মনোযোগ সরে গেলে আবার কাজে ফিরতে পারি।', 'construct' => 'attention_regulation', 'options' => 'ease'],
                    ['text' => 'I can remember two or three instructions while working. / কাজের সময় দুই বা তিনটি নির্দেশ মনে রাখতে পারি।', 'construct' => 'working_memory', 'options' => 'ease'],
                    ['text' => 'I can break a large task into smaller steps. / বড় কাজকে ছোট ধাপে ভাগ করতে পারি।', 'construct' => 'planning', 'options' => 'ease'],
                    ['text' => 'I can try a different approach when the first one does not work. / প্রথম উপায় কাজ না করলে অন্যভাবে চেষ্টা করতে পারি।', 'construct' => 'cognitive_flexibility', 'options' => 'ease'],
                    ['text' => 'I can pause before acting when I am excited or upset. / উত্তেজিত বা মন খারাপ হলে কাজ করার আগে থামতে পারি।', 'construct' => 'inhibitory_control', 'options' => 'ease'],
                ],
            ]),
            array_merge($base, [
                'code' => 'CHILD-SOCIAL-EMOTIONAL', 'name' => 'Feelings and Relationships / অনুভূতি ও সম্পর্ক', 'visible_to' => 'child',
                'domain' => 'social_emotional', 'constructs' => ['emotion_awareness', 'empathy', 'perspective', 'repair', 'help_seeking'],
                'description' => 'How a learner notices emotions, understands others, repairs conflict, and asks for help. / একজন শিক্ষার্থী কীভাবে অনুভূতি বোঝে, অন্যকে বুঝতে পারে, বিরোধ মেটায় ও সাহায্য চায়।',
                'questions' => [
                    ['text' => 'I can name what I am feeling. / আমি কী অনুভব করছি তা বলতে পারি।', 'construct' => 'emotion_awareness', 'options' => 'ease'],
                    ['text' => 'I notice when someone may need support. / কারও সাহায্য দরকার হতে পারে তা বুঝতে পারি।', 'construct' => 'empathy', 'options' => 'ease'],
                    ['text' => 'I can understand that someone may see the same event differently. / একই ঘটনা অন্য কেউ ভিন্নভাবে দেখতে পারে তা বুঝতে পারি।', 'construct' => 'perspective_taking', 'options' => 'ease'],
                    ['text' => 'After a disagreement, I can take one step toward making things better. / মতবিরোধের পর পরিস্থিতি ভালো করতে একটি পদক্ষেপ নিতে পারি।', 'construct' => 'relationship_repair', 'options' => 'ease'],
                    ['text' => 'I know how to ask a trusted adult for help. / বিশ্বস্ত বড় কারও কাছে কীভাবে সাহায্য চাইতে হয় তা জানি।', 'construct' => 'help_seeking', 'options' => 'ease'],
                ],
            ]),
            array_merge($base, [
                'code' => 'CHILD-POTENTIAL-INTERESTS', 'name' => 'Interests and Potential / আগ্রহ ও সম্ভাবনা', 'visible_to' => 'child',
                'domain' => 'student_potential', 'constructs' => ['curiosity', 'creativity', 'persistence', 'leadership', 'purpose'],
                'description' => 'A strengths-focused view of curiosity, expression, initiative, and emerging interests. / কৌতূহল, প্রকাশ, উদ্যোগ ও বিকশিত আগ্রহের শক্তিকেন্দ্রিক চিত্র।',
                'questions' => [
                    ['text' => 'I ask questions because I want to understand how something works. / কিছু কীভাবে কাজ করে বুঝতে আমি প্রশ্ন করি।', 'construct' => 'curiosity', 'options' => 'agreement'],
                    ['text' => 'I enjoy making or imagining something in my own way. / নিজের মতো করে কিছু বানাতে বা কল্পনা করতে ভালো লাগে।', 'construct' => 'creativity', 'options' => 'agreement'],
                    ['text' => 'I keep trying when something matters to me. / কোনো কিছু আমার কাছে গুরুত্বপূর্ণ হলে চেষ্টা চালিয়ে যাই।', 'construct' => 'persistence', 'options' => 'agreement'],
                    ['text' => 'I like helping a group decide what to do next. / দলকে পরের কাজ ঠিক করতে সাহায্য করতে ভালো লাগে।', 'construct' => 'leadership', 'options' => 'agreement'],
                    ['text' => 'I can name something I would like to become better at. / কোন বিষয়ে আরও ভালো হতে চাই তা বলতে পারি।', 'construct' => 'purpose', 'options' => 'agreement'],
                ],
            ]),
            array_merge($base, [
                'code' => 'PARENT-DEVELOPMENT-CONTEXT', 'name' => 'Home Development Context / বাড়ির বিকাশের প্রেক্ষাপট', 'visible_to' => 'parent',
                'domain' => 'development_context', 'constructs' => ['sleep', 'activity', 'routine', 'screen_transition', 'connection'],
                'description' => 'Family observations that help place learning and wellbeing signals in context. / শেখা ও ভালো থাকার সংকেতকে প্রেক্ষাপটে বুঝতে পরিবারের পর্যবেক্ষণ।',
                'questions' => [
                    ['text' => 'The child follows a reasonably consistent sleep routine. / শিশুটি মোটামুটি নিয়মিত ঘুমের রুটিন অনুসরণ করে।', 'construct' => 'sleep_routine', 'options' => 'frequency_adult'],
                    ['text' => 'The child has opportunities for movement or active play. / শিশুটি নড়াচড়া বা সক্রিয় খেলার সুযোগ পায়।', 'construct' => 'physical_activity', 'options' => 'frequency_adult'],
                    ['text' => 'The child can move between screen and non-screen activities with support. / সহায়তায় শিশুটি স্ক্রিন ও স্ক্রিনবিহীন কাজের মধ্যে পরিবর্তন করতে পারে।', 'construct' => 'screen_transition', 'options' => 'frequency_adult'],
                    ['text' => 'The child has a predictable way to begin homework or home learning. / বাড়ির পড়া শুরু করার একটি পরিচিত পদ্ধতি শিশুটির আছে।', 'construct' => 'learning_routine', 'options' => 'frequency_adult'],
                    ['text' => 'The child shares interests or concerns with a trusted person. / শিশুটি বিশ্বস্ত কারও সঙ্গে আগ্রহ বা উদ্বেগের কথা বলে।', 'construct' => 'family_connection', 'options' => 'frequency_adult'],
                ],
            ]),
            array_merge($base, [
                'code' => 'PARENT-STRENGTHS-OBSERVATION', 'name' => 'Strengths at Home / বাড়িতে দেখা শক্তি', 'visible_to' => 'parent',
                'domain' => 'student_potential', 'constructs' => ['interests', 'creativity', 'initiative', 'care', 'persistence'],
                'description' => 'What families notice about the child outside formal academic tasks. / আনুষ্ঠানিক পড়াশোনার বাইরে শিশুর বিষয়ে পরিবার যা লক্ষ্য করে।',
                'questions' => [
                    ['text' => 'The child becomes deeply engaged in particular interests. / নির্দিষ্ট আগ্রহে শিশুটি গভীরভাবে যুক্ত হয়।', 'construct' => 'interest_depth', 'options' => 'frequency_adult'],
                    ['text' => 'The child creates original stories, designs, solutions, or play. / শিশুটি মৌলিক গল্প, নকশা, সমাধান বা খেলা তৈরি করে।', 'construct' => 'creativity', 'options' => 'frequency_adult'],
                    ['text' => 'The child starts meaningful activities without being prompted. / মনে করিয়ে না দিলেও শিশুটি অর্থপূর্ণ কাজ শুরু করে।', 'construct' => 'initiative', 'options' => 'frequency_adult'],
                    ['text' => 'The child notices and responds to the needs of people or animals. / মানুষ বা প্রাণীর প্রয়োজন বুঝে শিশুটি সাড়া দেয়।', 'construct' => 'care_empathy', 'options' => 'frequency_adult'],
                    ['text' => 'The child returns to a difficult activity that matters to them. / গুরুত্বপূর্ণ কঠিন কাজে শিশুটি আবার ফিরে যায়।', 'construct' => 'persistence', 'options' => 'frequency_adult'],
                ],
            ]),
            array_merge($base, [
                'code' => 'TEACHER-LEARNING-OBSERVATION', 'name' => 'Classroom Learning Observation / শ্রেণিকক্ষের শেখার পর্যবেক্ষণ', 'visible_to' => 'teacher',
                'domain' => 'pedagogy', 'constructs' => ['engagement', 'expression', 'scaffolding', 'transfer', 'collaboration'],
                'description' => 'Structured classroom evidence about how a learner engages, expresses understanding, and uses support. / একজন শিক্ষার্থী কীভাবে যুক্ত হয়, বোঝাপড়া প্রকাশ করে ও সহায়তা ব্যবহার করে তার গঠিত শ্রেণিকক্ষভিত্তিক প্রমাণ।',
                'questions' => [
                    ['text' => 'The learner engages more deeply when the task connects with a known interest. / পরিচিত আগ্রহের সঙ্গে কাজ যুক্ত হলে শিক্ষার্থী আরও গভীরভাবে অংশ নেয়।', 'construct' => 'interest_linked_engagement', 'options' => 'frequency_adult'],
                    ['text' => 'The learner demonstrates understanding through more than one form of expression. / শিক্ষার্থী একাধিক প্রকাশভঙ্গির মাধ্যমে বোঝাপড়া দেখায়।', 'construct' => 'multimodal_expression', 'options' => 'frequency_adult'],
                    ['text' => 'A small scaffold helps the learner continue without reducing the learning goal. / শেখার লক্ষ্য না কমিয়ে অল্প সহায়তা শিক্ষার্থীকে এগিয়ে যেতে সাহায্য করে।', 'construct' => 'scaffold_response', 'options' => 'frequency_adult'],
                    ['text' => 'The learner applies a strategy from one task to another. / শিক্ষার্থী একটি কাজের কৌশল অন্য কাজে প্রয়োগ করে।', 'construct' => 'learning_transfer', 'options' => 'frequency_adult'],
                    ['text' => 'The learner contributes constructively during paired or group work. / জোড়া বা দলগত কাজে শিক্ষার্থী গঠনমূলক অবদান রাখে।', 'construct' => 'collaborative_learning', 'options' => 'frequency_adult'],
                ],
            ]),
            array_merge($base, [
                'code' => 'TEACHER-REGULATION-OBSERVATION', 'name' => 'Classroom Regulation Observation / শ্রেণিকক্ষের স্বনিয়ন্ত্রণ পর্যবেক্ষণ', 'visible_to' => 'teacher',
                'domain' => 'psychology', 'constructs' => ['attention', 'transition', 'frustration', 'help_seeking', 'recovery'],
                'description' => 'Contextual observations of attention, transitions, frustration, recovery, and support use. / মনোযোগ, কাজ বদল, হতাশা, পুনরুদ্ধার ও সহায়তা ব্যবহারের প্রেক্ষাপটভিত্তিক পর্যবেক্ষণ।',
                'questions' => [
                    ['text' => 'The learner returns to a task after an ordinary classroom distraction. / শ্রেণিকক্ষের সাধারণ বিভ্রান্তির পর শিক্ষার্থী আবার কাজে ফিরে আসে।', 'construct' => 'attention_regulation', 'options' => 'frequency_adult'],
                    ['text' => 'The learner moves between activities with the expected level of support. / প্রত্যাশিত সহায়তায় শিক্ষার্থী একটি কাজ থেকে অন্য কাজে যায়।', 'construct' => 'transition_regulation', 'options' => 'frequency_adult'],
                    ['text' => 'The learner can continue after a mistake or manageable frustration. / ভুল বা সামলানো সম্ভব এমন হতাশার পর শিক্ষার্থী এগিয়ে যেতে পারে।', 'construct' => 'frustration_tolerance', 'options' => 'frequency_adult'],
                    ['text' => 'The learner asks for help in a way that supports progress. / শিক্ষার্থী এমনভাবে সাহায্য চায় যা অগ্রগতিতে সহায়ক।', 'construct' => 'help_seeking', 'options' => 'frequency_adult'],
                    ['text' => 'After becoming upset, the learner uses a strategy or support to re-engage. / মন খারাপের পর শিক্ষার্থী আবার যুক্ত হতে কৌশল বা সহায়তা ব্যবহার করে।', 'construct' => 'recovery', 'options' => 'frequency_adult'],
                ],
            ]),
        ];
    }
}
