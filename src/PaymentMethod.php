<?php

namespace ilhamuket\Tripay;

/**
 * Available Payment Methods
 */
class PaymentMethod
{
    // Virtual Account
    public const MYBVA = 'MYBVA';           // Maybank Virtual Account
    public const PERMATAVA = 'PERMATAVA';   // Permata Virtual Account
    public const BNIVA = 'BNIVA';           // BNI Virtual Account
    public const BRIVA = 'BRIVA';           // BRI Virtual Account
    public const MANDIRIVA = 'MANDIRIVA';   // Mandiri Virtual Account
    public const BCAVA = 'BCAVA';           // BCA Virtual Account
    public const SMSVA = 'SMSVA';           // Sinarmas Virtual Account
    public const MUAMALATVA = 'MUAMALATVA'; // Muamalat Virtual Account
    public const CIMBVA = 'CIMBVA';         // CIMB Virtual Account
    public const SAMPOERNAVA = 'SAMPOERNAVA'; // Sahabat Sampoerna VA
    public const BSIVA = 'BSIVA';           // BSI Virtual Account
    public const OCBCVA = 'OCBCVA';         // OCBC Virtual Account

    // Convenience Store
    public const ALFAMART = 'ALFAMART';     // Alfamart
    public const INDOMARET = 'INDOMARET';   // Indomaret
    public const ALFAMIDI = 'ALFAMIDI';     // Alfamidi

    // E-Wallet
    public const QRIS = 'QRIS';             // QRIS
    public const QRISC = 'QRISC';           // QRIS (Customizable)
    public const QRIS2 = 'QRIS2';           // QRIS 2
    public const OVO = 'OVO';               // OVO
    public const DANA = 'DANA';             // DANA
    public const SHOPEEPAY = 'SHOPEEPAY';   // ShopeePay

    /**
     * Get all Virtual Account methods
     */
    public static function virtualAccounts(): array
    {
        return [
            self::MYBVA,
            self::PERMATAVA,
            self::BNIVA,
            self::BRIVA,
            self::MANDIRIVA,
            self::BCAVA,
            self::SMSVA,
            self::MUAMALATVA,
            self::CIMBVA,
            self::SAMPOERNAVA,
            self::BSIVA,
            self::OCBCVA,
        ];
    }

    /**
     * Get all Convenience Store methods
     */
    public static function convenienceStores(): array
    {
        return [
            self::ALFAMART,
            self::INDOMARET,
            self::ALFAMIDI,
        ];
    }

    /**
     * Get all E-Wallet methods
     */
    public static function eWallets(): array
    {
        return [
            self::QRIS,
            self::QRISC,
            self::QRIS2,
            self::OVO,
            self::DANA,
            self::SHOPEEPAY,
        ];
    }

    /**
     * Get all methods
     */
    public static function all(): array
    {
        return array_merge(
            self::virtualAccounts(),
            self::convenienceStores(),
            self::eWallets()
        );
    }

    /**
     * Check if method is Virtual Account
     */
    public static function isVirtualAccount(string $method): bool
    {
        return in_array($method, self::virtualAccounts());
    }

    /**
     * Check if method is Convenience Store
     */
    public static function isConvenienceStore(string $method): bool
    {
        return in_array($method, self::convenienceStores());
    }

    /**
     * Check if method is E-Wallet
     */
    public static function isEWallet(string $method): bool
    {
        return in_array($method, self::eWallets());
    }

    /**
     * Check if method is QRIS
     */
    public static function isQris(string $method): bool
    {
        return in_array($method, [self::QRIS, self::QRISC, self::QRIS2]);
    }
}
