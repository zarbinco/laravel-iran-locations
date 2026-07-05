<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zarbin\IranLocations\Support\LocationModelResolver;

return new class extends Migration
{
    public function up(): void
    {
        $tables = $this->tables();

        Schema::create($tables['provinces'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['provinces'])->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'deprecated_at'], 'iran_provinces_status_idx');
        });

        Schema::create($tables['counties'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('province_id')->constrained($tables['provinces'])->restrictOnDelete();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['counties'])->nullOnDelete();
            $table->timestamps();

            $table->index(['province_id', 'is_active'], 'iran_counties_province_status_idx');
            $table->index(['province_id', 'normalized_name'], 'iran_counties_province_search_idx');
            $table->index(['is_active', 'deprecated_at'], 'iran_counties_status_idx');
        });

        Schema::create($tables['official_districts'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('province_id')->constrained($tables['provinces'])->restrictOnDelete();
            $table->foreignId('county_id')->constrained($tables['counties'])->restrictOnDelete();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['official_districts'])->nullOnDelete();
            $table->timestamps();

            $table->index(['province_id', 'is_active'], 'iran_official_districts_province_status_idx');
            $table->index(['county_id', 'is_active'], 'iran_official_districts_county_status_idx');
            $table->index(['county_id', 'normalized_name'], 'iran_official_districts_county_search_idx');
            $table->index(['is_active', 'deprecated_at'], 'iran_official_districts_status_idx');
        });

        Schema::create($tables['rural_districts'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('province_id')->constrained($tables['provinces'])->restrictOnDelete();
            $table->foreignId('county_id')->constrained($tables['counties'])->restrictOnDelete();
            $table->foreignId('official_district_id')->constrained($tables['official_districts'])->restrictOnDelete();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['rural_districts'])->nullOnDelete();
            $table->timestamps();

            $table->index(['province_id', 'is_active'], 'iran_rural_districts_province_status_idx');
            $table->index(['county_id', 'is_active'], 'iran_rural_districts_county_status_idx');
            $table->index(['official_district_id', 'is_active'], 'iran_rural_districts_official_status_idx');
            $table->index(['is_active', 'deprecated_at'], 'iran_rural_districts_status_idx');
        });

        Schema::create($tables['cities'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('province_id')->constrained($tables['provinces'])->restrictOnDelete();
            $table->foreignId('county_id')->nullable()->constrained($tables['counties'])->nullOnDelete();
            $table->foreignId('official_district_id')->nullable()->constrained($tables['official_districts'])->nullOnDelete();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $table->boolean('is_province_capital')->default(false);
            $this->coordinates($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['cities'])->nullOnDelete();
            $table->timestamps();

            $table->index(['province_id', 'is_active'], 'iran_cities_province_status_idx');
            $table->index(['province_id', 'normalized_name'], 'iran_cities_province_search_idx');
            $table->index(['county_id', 'is_active'], 'iran_cities_county_status_idx');
            $table->index(['official_district_id', 'is_active'], 'iran_cities_official_status_idx');
            $table->index(['is_active', 'deprecated_at'], 'iran_cities_status_idx');
        });

        Schema::create($tables['city_regions'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('city_id')->constrained($tables['cities'])->restrictOnDelete();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('number')->nullable();
            $this->nameColumns($table);
            $table->string('type')->default('municipal_region')->index();
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['city_regions'])->nullOnDelete();
            $table->timestamps();

            $table->index(['city_id', 'number'], 'iran_regions_city_number_idx');
            $table->index(['city_id', 'is_active'], 'iran_regions_city_status_idx');
        });

        Schema::create($tables['city_areas'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('city_region_id')->constrained($tables['city_regions'])->restrictOnDelete();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('number')->nullable();
            $this->nameColumns($table, includeEnglishName: false);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['city_areas'])->nullOnDelete();
            $table->timestamps();

            $table->index(['city_region_id', 'number'], 'iran_areas_region_number_idx');
            $table->index(['city_region_id', 'is_active'], 'iran_areas_region_status_idx');
        });

        Schema::create($tables['neighborhoods'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('city_id')->constrained($tables['cities'])->restrictOnDelete();
            $table->foreignId('default_city_region_id')->nullable()->constrained($tables['city_regions'])->nullOnDelete();
            $table->foreignId('default_city_area_id')->nullable()->constrained($tables['city_areas'])->nullOnDelete();
            $table->string('code')->unique();
            $this->nameColumns($table);
            $table->string('type')->default('neighborhood')->index();
            $this->coordinates($table);
            $this->lifecycleColumns($table);
            $table->foreignId('replaced_by_id')->nullable()->constrained($tables['neighborhoods'])->nullOnDelete();
            $table->timestamps();

            $table->index(['city_id', 'is_active'], 'iran_neighborhoods_city_status_idx');
            $table->index(['city_id', 'normalized_name'], 'iran_neighborhoods_city_search_idx');
            $table->index(['default_city_region_id', 'is_active'], 'iran_neighborhoods_region_idx');
            $table->index(['default_city_area_id', 'is_active'], 'iran_neighborhoods_area_idx');
            $table->index(['type', 'is_active'], 'iran_neighborhoods_type_status_idx');
        });

        Schema::create($tables['neighborhood_region'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('neighborhood_id')->constrained($tables['neighborhoods'])->cascadeOnDelete();
            $table->foreignId('city_region_id')->constrained($tables['city_regions'])->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('source')->default('package')->index();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->timestamps();

            $table->unique(['neighborhood_id', 'city_region_id'], 'iran_neighborhood_region_unique');
            $table->index(['city_region_id', 'is_primary'], 'iran_neighborhood_region_primary_idx');
        });

        Schema::create($tables['location_aliases'], function (Blueprint $table): void {
            $table->id();
            $table->string('location_type', 64);
            $table->unsignedBigInteger('location_id');
            $table->string('alias');
            $table->string('normalized_alias')->index();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('package');
            $table->string('source_version')->nullable();
            $table->string('data_version')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id'], 'iran_location_aliases_location_idx');
            $table->unique(['location_type', 'location_id', 'normalized_alias'], 'iran_location_aliases_target_alias_unique');
            $table->index(['source', 'is_active', 'deprecated_at'], 'iran_location_aliases_source_status_idx');
            $table->index(['is_active', 'deprecated_at'], 'iran_location_aliases_status_idx');
        });

        Schema::create($tables['data_versions'], function (Blueprint $table): void {
            $table->id();
            $table->string('data_version')->index();
            $table->string('package_version')->nullable();
            $table->string('checksum')->default('');
            $table->json('summary')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['data_version', 'checksum'], 'iran_data_versions_version_checksum_unique');
        });
    }

    public function down(): void
    {
        $tables = $this->tables();

        Schema::dropIfExists($tables['data_versions']);
        Schema::dropIfExists($tables['location_aliases']);
        Schema::dropIfExists($tables['neighborhood_region']);
        Schema::dropIfExists($tables['neighborhoods']);
        Schema::dropIfExists($tables['city_areas']);
        Schema::dropIfExists($tables['city_regions']);
        Schema::dropIfExists($tables['cities']);
        Schema::dropIfExists($tables['rural_districts']);
        Schema::dropIfExists($tables['official_districts']);
        Schema::dropIfExists($tables['counties']);
        Schema::dropIfExists($tables['provinces']);
    }

    private function nameColumns(Blueprint $table, bool $includeEnglishName = true): void
    {
        $table->string('name_fa');

        if ($includeEnglishName) {
            $table->string('name_en')->nullable();
        }

        $table->string('slug')->nullable()->index();
        $table->string('normalized_name')->index();
        $table->string('display_name_fa')->nullable();
    }

    private function lifecycleColumns(Blueprint $table): void
    {
        $table->boolean('is_active')->default(true)->index();
        $table->string('source')->default('package')->index();
        $table->string('source_version')->nullable();
        $table->string('data_version')->nullable()->index();
        $table->timestamp('deprecated_at')->nullable();
    }

    private function coordinates(Blueprint $table): void
    {
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
    }

    /**
     * @return array<string, string>
     */
    private function tables(): array
    {
        return [
            'provinces' => LocationModelResolver::table('province'),
            'counties' => LocationModelResolver::table('county'),
            'official_districts' => LocationModelResolver::table('official_district'),
            'rural_districts' => LocationModelResolver::table('rural_district'),
            'cities' => LocationModelResolver::table('city'),
            'city_regions' => LocationModelResolver::table('city_region'),
            'city_areas' => LocationModelResolver::table('city_area'),
            'neighborhoods' => LocationModelResolver::table('neighborhood'),
            'neighborhood_region' => LocationModelResolver::table('neighborhood_region'),
            'location_aliases' => LocationModelResolver::table('location_alias'),
            'data_versions' => LocationModelResolver::table('data_version'),
        ];
    }
};
