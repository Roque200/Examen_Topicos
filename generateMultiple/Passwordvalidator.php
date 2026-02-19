<?php
class PasswordValidator
{
    /**
     * @param string 
     * @param array  
     * @return array 
     */
    public static function validate(string $password, array $requirements = []): array
    {
        $requirements = array_merge([
            'minLength'        => 8,
            'maxLength'        => 128,
            'requireUppercase' => false,
            'requireLowercase' => false,
            'requireNumbers'   => false,
            'requireSymbols'   => false,
        ], $requirements);

        $checks  = [];
        $passed  = 0;
        $total   = 0;

        $total++;
        $lenOk          = strlen($password) >= (int)$requirements['minLength'];
        $checks['minLength'] = [
            'required' => (int)$requirements['minLength'],
            'actual'   => strlen($password),
            'passed'   => $lenOk,
        ];
        if ($lenOk) $passed++;

        if (strlen($password) > (int)$requirements['maxLength']) {
            $checks['maxLength'] = [
                'required' => (int)$requirements['maxLength'],
                'actual'   => strlen($password),
                'passed'   => false,
            ];
            $total++;
        }

        if ($requirements['requireUppercase']) {
            $total++;
            $ok = (bool)preg_match('/[A-Z]/', $password);
            $checks['requireUppercase'] = ['passed' => $ok];
            if ($ok) $passed++;
        }

        if ($requirements['requireLowercase']) {
            $total++;
            $ok = (bool)preg_match('/[a-z]/', $password);
            $checks['requireLowercase'] = ['passed' => $ok];
            if ($ok) $passed++;
        }

        if ($requirements['requireNumbers']) {
            $total++;
            $ok = (bool)preg_match('/[0-9]/', $password);
            $checks['requireNumbers'] = ['passed' => $ok];
            if ($ok) $passed++;
        }

        if ($requirements['requireSymbols']) {
            $total++;
            $ok = (bool)preg_match('/[^A-Za-z0-9]/', $password);
            $checks['requireSymbols'] = ['passed' => $ok];
            if ($ok) $passed++;
        }

        $score   = $total > 0 ? round(($passed / $total) * 100) : 0;
        $isValid = ($passed === $total);

        $strength = self::calculateStrength($password);

        return [
            'valid'    => $isValid,
            'score'    => $score,
            'strength' => $strength,
            'checks'   => $checks,
        ];
    }

    private static function calculateStrength(string $password): string
    {
        $len     = strlen($password);
        $entropy = 0;

        if (preg_match('/[a-z]/', $password)) $entropy += 26;
        if (preg_match('/[A-Z]/', $password)) $entropy += 26;
        if (preg_match('/[0-9]/', $password)) $entropy += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $entropy += 32;

        $bits = $len * log($entropy ?: 1, 2);

        if ($bits < 28)      return 'muy_debil';
        elseif ($bits < 36)  return 'debil';
        elseif ($bits < 60)  return 'moderada';
        elseif ($bits < 120) return 'fuerte';
        else                 return 'muy_fuerte';
    }
}
?>