<?php

if (!function_exists('splitFullName')) {
    function splitFullName($fullName)
    {
        $parts = explode(' ', $fullName);

        if (count($parts) < 2) {
            // Jika hanya ada satu bagian, anggap sebagai nama depan
            $firstName = $fullName;
            $lastName = $fullName;
        } else {
            // Nama depan adalah bagian pertama
            $firstName = array_shift($parts);
            // Nama belakang adalah gabungan dari sisa bagian
            $lastName = implode(' ', $parts);
        }

        return [$firstName, $lastName];
    }
}