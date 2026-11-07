<?php

namespace App\Services;

class SyntheticBenchmarkService
{
    public function report(): array
    {
        $rows = collect(range(1, 240))->map(fn ($id) => $this->case($id));
        $groups = ['all' => $rows, 'complete' => $rows->where('coverage', 'complete'), 'partial' => $rows->where('coverage', 'partial')];

        return [
            'status' => 'engineering_sanity_check_only',
            'dataset' => ['cases' => $rows->count(), 'synthetic' => true, 'seed' => 20261107, 'complete_cases' => $groups['complete']->count(), 'partial_cases' => $groups['partial']->count()],
            'metrics' => collect($groups)->map(fn ($items) => $this->metrics($items))->all(),
            'dimensions' => ['distress_signals', 'daily_functioning', 'protective_factors', 'contextual_stressors', 'multi_source_agreement'],
            'design_basis' => [
                ['title' => 'UNICEF MMAPP measurement toolkit', 'url' => 'https://data.unicef.org/resources/mmapp-toolkit/'],
                ['title' => 'UNICEF multisectoral MHPSS framework', 'url' => 'https://www.unicef.org/reports/global-multisectoral-operational-framework'],
                ['title' => 'WHO child and young people mental-health service guidance', 'url' => 'https://www.who.int/publications/i/item/9789240100374'],
            ],
            'limitations' => [
                'Synthetic labels are generated for software testing and do not establish clinical validity.',
                'Performance on real students, languages, cultures, ages, disabilities, and missing-data patterns is unknown.',
                'No autonomous referral or diagnosis is permitted; a qualified human reviews evidence and uncertainty.',
            ],
        ];
    }

    private function case(int $id): array
    {
        $noise = fn (int $salt) => (($id * (37 + $salt * 11)) % 101) / 100;
        $distress = $noise(1); $functioning = $noise(2); $stressors = $noise(3); $protective = $noise(4);
        $coverage = $id % 3 === 0 ? 'partial' : 'complete';
        $latent = .38 * $distress + .3 * $functioning + .2 * $stressors - .18 * $protective + ($noise(5) - .5) * .14;
        $observed = .4 * $distress + .28 * $functioning + .18 * $stressors - .14 * $protective;
        if ($coverage === 'partial') $observed = .7 * $observed + .3 * ($noise(6) - .1);

        return ['coverage' => $coverage, 'expected' => $latent >= .42, 'predicted' => $observed >= .42];
    }

    private function metrics($rows): array
    {
        $tp = $rows->where('expected', true)->where('predicted', true)->count();
        $tn = $rows->where('expected', false)->where('predicted', false)->count();
        $fp = $rows->where('expected', false)->where('predicted', true)->count();
        $fn = $rows->where('expected', true)->where('predicted', false)->count();
        $ratio = fn ($a, $b) => $b ? round($a / $b, 3) : null;
        $precision = $ratio($tp, $tp + $fp); $recall = $ratio($tp, $tp + $fn);

        return [
            'n' => $rows->count(), 'sensitivity' => $recall, 'specificity' => $ratio($tn, $tn + $fp),
            'precision' => $precision, 'f1' => ($precision !== null && $recall !== null && ($precision + $recall)) ? round(2 * $precision * $recall / ($precision + $recall), 3) : null,
            'confusion' => compact('tp', 'tn', 'fp', 'fn'),
        ];
    }
}
