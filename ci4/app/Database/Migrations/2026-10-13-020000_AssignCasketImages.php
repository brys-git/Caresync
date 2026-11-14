<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Points each casket package at its photo in public/assets/images/. Matched by
 * package name (not id); paths are stored relative to public/, never as
 * absolute Windows paths. Safe to re-run.
 */
class AssignCasketImages extends Migration
{
    private const IMAGES = [
        'regular wood casket'             => 'assets/images/Regular_Wood_Casket.jfif',
        'oversized wood casket'           => 'assets/images/Oversized_Wood_Casket.jfif',
        'regular half-glass metal casket' => 'assets/images/Regular_Half-Glass_Metal_Casket.jfif',
        'regular full-glass metal casket' => 'assets/images/Regular_Full-Glass_Metal_Casket.jfif',
        'oversized metal casket'          => 'assets/images/Oversized_Metal_Casket.jfif',
    ];

    public function up()
    {
        foreach (self::IMAGES as $name => $path) {
            $this->db->table('packages')->where('LOWER(package_name)', $name)->update(['image_path' => $path]);
        }
    }

    public function down()
    {
        // Earlier lowercase paths are not restored; the files were renamed.
    }
}
