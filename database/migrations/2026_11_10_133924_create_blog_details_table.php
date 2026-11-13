
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_details', function (Blueprint $table) {

            $table->foreignId('postid')
                  ->primary()
                  ->constrained('user_blogs', 'postid')
                  ->cascadeOnDelete();

            $table->longText('longpara');
            $table->json('keywords')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('viewcount')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_details');
    }
};
