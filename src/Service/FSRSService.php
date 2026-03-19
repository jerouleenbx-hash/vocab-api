<?php

namespace App\Service;

class FSRSService
{
    public function update(array $p, int $grade): array
    {
        $now = new \DateTime();

        // Initialisation
        $S = $p['stability'] ?? 0.1;
        $D = $p['difficulty'] ?? 5.0;

        // 0-3 = échec / 4-5 = réussite
        if ($grade < 3) {
            $S = 0.1;
            $D = min(10, $D + 1);
            $p['lapses'] = ($p['lapses'] ?? 0) + 1;
        } else {
            // Mise à jour FSRS simplifiée
            $S = $S * (1 + 0.15 * (6 - $D));
            $D = max(1, $D - 0.1 * ($grade - 3));
        }

        // Intervalle basé sur stabilité
        $intervalDays = max(1, round($S));

        return [
            'stability' => $S,
            'difficulty' => $D,
            'next_review' => $now->modify("+$intervalDays days"),
            'last_review' => new \DateTime(),
            'reps' => ($p['reps'] ?? 0) + 1
        ];
    }



    function retrievability(array $p): float
    {
        // ❗ mot jamais vu
        if (!$p || empty($p['last_review'])) {
            return 0;
        }

        $now = new \DateTime();
        $lastReview = new \DateTime($p['last_review']);

        // temps écoulé en jours
        $days = max(0, $now->diff($lastReview)->days);

        // stabilité (évite division par 0)
        $S = max(1, (float)$p['stability']);

        return exp(-$days / $S);
    }

    function masteryScore(array $p): float
    {
        // 🔴 jamais vu
        if (!$p || !$p['last_review']) {
            return 0;
        }

        $S = max(1, $p['stability']);
        $D = $p['difficulty'] ?? 5;

        $reps = $p['reps'] ?? 0;
        $lapses = $p['lapses'] ?? 0;

        $success = $reps > 0 ? $reps / ($reps + $lapses) : 0;

        $stabilityScore = min(1, $S / 15);
        $difficultyScore = 1 - ($D / 10);

        $R = $this->retrievability($p);

        return round(
            0.4 * $stabilityScore +
            0.2 * $difficultyScore +
            0.2 * $success +
            0.2 * $R,
            2
        );
    }
}