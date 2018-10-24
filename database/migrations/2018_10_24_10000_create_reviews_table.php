<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rating');
            $table->integer('customer_service_rating')->nullable();
            $table->integer('quality_rating')->nullable();
            $table->integer('friendly_rating')->nullable();
            $table->integer('price_rating')->nullable();
            $table->string('recommend', 3)->nullable();
            $table->enum('department', ['Sales', 'Service', 'Parts']);
            $table->string('title');
            $table->string('body');
            $table->morphs('reviewrateable');
            $table->morphs('author');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
