<?php
require_once __DIR__ . '/Password.php';
class PasswordFactory
{
    /**
     * @param int   
     * @param array 
     * @return Password
     */
    public static function create(int $length, array $opts = []): Password
    {
        return new Password($length, $opts);
    }

    /**
     * @param array 
     * @param int   
     * @return Password
     */
    public static function fromRequest(array $data, int $default_length = 16): Password
    {
        $length = isset($data['length']) ? (int)$data['length'] : $default_length;

        $opts = [
            'upper'           => isset($data['includeUppercase'])
                                    ? filter_var($data['includeUppercase'], FILTER_VALIDATE_BOOLEAN)
                                    : true,
            'lower'           => isset($data['includeLowercase'])
                                    ? filter_var($data['includeLowercase'], FILTER_VALIDATE_BOOLEAN)
                                    : true,
            'digits'          => isset($data['includeNumbers'])
                                    ? filter_var($data['includeNumbers'], FILTER_VALIDATE_BOOLEAN)
                                    : true,
            'symbols'         => isset($data['includeSymbols'])
                                    ? filter_var($data['includeSymbols'], FILTER_VALIDATE_BOOLEAN)
                                    : false,
            'avoid_ambiguous' => isset($data['excludeAmbiguous'])
                                    ? filter_var($data['excludeAmbiguous'], FILTER_VALIDATE_BOOLEAN)
                                    : false,
            'exclude'         => $data['exclude'] ?? '',
            'require_each'    => isset($data['requireEach'])
                                    ? filter_var($data['requireEach'], FILTER_VALIDATE_BOOLEAN)
                                    : true,
        ];

        return self::create($length, $opts);
    }
}
?>
