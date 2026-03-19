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
}