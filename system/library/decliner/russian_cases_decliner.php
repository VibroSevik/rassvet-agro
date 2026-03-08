<?php

namespace Decliner;

class RussianCasesDecliner
{
    /**
     * Склоняет каждое слово из словосочетания из Именительного падежа в падеж, указанный в {@link RussianCases списке падежей}.
     * @param string $phrase словосочетание
     * @param string $case падеж из {@link RussianCases списка падежей}
     * @param bool $strictNominativeCase исключение, если слово не в Именительном падеже
     * @return string
     */
    public function declinePhrase(string $phrase, string $case, bool $strictNominativeCase = false): string
    {
        $declinedPhrase = [];
        foreach (explode(' ', $phrase) as $word) {
            $declinedPhrase[] = $this->declineWord($word, $case, $strictNominativeCase);
        }
        return implode(' ', $declinedPhrase);
    }

    /**
     * Склоняет слово из Именительного падежа в падеж, указанный в {@link RussianCases списке падежей}.
     * @param string $word слово в Именительном падеже (без выброса исключения)
     * @param string $case падеж из {@link RussianCases списка падежей}
     * @param bool $strictNominativeCase исключение, если слово не в Именительном падеже
     * @return string слово в выбранном падеже
     */
    public function declineWord(string $word, string $case, bool $strictNominativeCase = false): string {

        switch ($case) {
            case RussianCases::INSTRUMENTAL:
                return $this->declineToInstrumentalCase($word, $strictNominativeCase);
            case RussianCases::ACCUSATIVE:
                return $this->declineToAccusativeCase($word, $strictNominativeCase);

            default:
                throw new \RuntimeException('Decline поддерживает только Творительный и Винительный падежи.');
        }
    }

    /**
     * Склоняет слово из Именительного падежа в Творительный падеж. <br>
     * Правила склонения:
     * 1) Если существительное оканчивается на -а, -я, то заменить на -ой, -ей;
     * 2) Если существительное оканчивается на -ие, -ье, то заменить на -ием, -ьем;
     * 3) Если существительное оканчивается на -ь, то заменить на -ью;
     * 4) Если существительное оканчивается на согласную, то добавить после неё -ом.
     * @param string $word слово в Именительном падеже
     * @param bool $strictNominativeCase исключение, если слово не в Именительном падеже
     * @return string слово в Творительном падеже
     */
    private function declineToInstrumentalCase(string $word, bool $strictNominativeCase = false): string
    {
        $rules = [
            '/а$/u' => 'ой',
            '/я$/u' => 'ей',

            '/ие$/u' => 'ием',
            '/ье$/u' => 'ьем',

            '/ь$/u' => 'ью',

            '/[бвгджзйклмнпрстфхцчшщ]$/u' => 'ом',
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }

        if ($strictNominativeCase) {
            throw new \RuntimeException('При склонении в Творительный падеж произошла ошибка.');
        }

        return $word;
    }

    /**
     * Склоняет слово из Именительного падежа в Винительный падеж. <br>
     * Правила склонения:
     * 1) Если существительное оканчивается на -а, -я, то заменить на -у, -ю; в противном случае ничего не менять.
     * @param string $word слово в Именительном падеже
     * @param bool $strictNominativeCase исключение, если слово не в Именительном падеже
     * @return string слово в Винительном падеже
     */
    private function declineToAccusativeCase(string $word, bool $strictNominativeCase = false): string
    {
        $rules = [
            '/а$/u' => 'у',
            '/я$/u' => 'ю',
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }

        if ($strictNominativeCase) {
            throw new \RuntimeException('При склонении в Винительный падеж произошла ошибка.');
        }

        return $word;
    }
}