<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Reemplaza setup_hash_passwords.php: cifra contraseñas que quedaron en texto plano. */
class HashearPasswordsLegacy extends Command
{
    protected $signature = 'usuarios:hashear-legacy';
    protected $description = 'Cifra con bcrypt las contraseñas de usuarios que aún están en texto plano';

    public function handle(): int
    {
        $n = 0;
        foreach (DB::table('usuarios')->get(['id', 'password']) as $u) {
            if (!Hash::isHashed($u->password)) {
                DB::table('usuarios')->where('id', $u->id)->update(['password' => Hash::make($u->password)]);
                $n++;
            }
        }
        $this->info("Contraseñas cifradas: $n");

        return self::SUCCESS;
    }
}
