
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_blogs', function (Blueprint $table) {

            $table->id('postid');

            $table->foreignId('userid')
                  ->constrained('users', 'id')
                  ->cascadeOnDelete();

            $table->string('title');
            $table->string('shortdesc');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blogs');
    }
};
