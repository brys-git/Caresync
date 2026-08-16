<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PackageServiceSeeder extends Seeder
{
    public function run()
    {
        // First, ensure we have the two main packages (Wood Casket and Metal Casket)
        // Clear existing data (child tables first to avoid FK conflicts)
        $this->db->table('package_inclusions')->where('inclusion_id >=', 0)->delete();
        $this->db->table('package_variants')->where('variant_id >=', 0)->delete();
        $this->db->table('add_ons')->where('addon_id >=', 0)->delete();
        $this->db->table('packages')->where('package_id >=', 0)->delete();

        // Insert the two main packages
        $packages = [
            [
                'package_name'        => 'Wood Casket',
                'package_type'        => 'funeral',
                'description'         => 'Traditional wood casket funeral package with multiple variants',
                'base_price'          => 20000.00,
                'is_customizable'     => 1,
                'is_available'        => 1,
                'status'              => 'active',
            ],
            [
                'package_name'        => 'Metal Casket',
                'package_type'        => 'funeral',
                'description'         => 'Premium metal casket funeral package with glass variants',
                'base_price'          => 45000.00,
                'is_customizable'     => 1,
                'is_available'        => 1,
                'status'              => 'active',
            ],
        ];
        $this->db->table('packages')->insertBatch($packages);

        // Get package IDs
        $woodPkg = $this->db->table('packages')->where('package_name', 'Wood Casket')->get()->getRow();
        $metalPkg = $this->db->table('packages')->where('package_name', 'Metal Casket')->get()->getRow();

        $woodPkgId = (int) $woodPkg->package_id;
        $metalPkgId = (int) $metalPkg->package_id;

        // Clear and insert package variants
        $this->db->table('package_variants')->where('variant_id >=', 0)->delete();

        $variants = [
            // Wood Casket variants
            [
                'package_id'   => $woodPkgId,
                'variant_name' => 'Regular',
                'description'  => 'Standard size wood casket',
                'base_price'   => 20000.00,
                'is_default'   => 1,
                'sort_order'   => 1,
                'status'       => 'active',
            ],
            [
                'package_id'   => $woodPkgId,
                'variant_name' => 'Oversized',
                'description'  => 'Larger size wood casket for oversized needs',
                'base_price'   => 50000.00,
                'is_default'   => 0,
                'sort_order'   => 2,
                'status'       => 'active',
            ],
            // Metal Casket variants
            [
                'package_id'   => $metalPkgId,
                'variant_name' => 'Half-Glass',
                'description'  => 'Metal casket with half-glass viewing window',
                'base_price'   => 45000.00,
                'is_default'   => 1,
                'sort_order'   => 1,
                'status'       => 'active',
            ],
            [
                'package_id'   => $metalPkgId,
                'variant_name' => 'Full-Glass',
                'description'  => 'Metal casket with full-glass viewing window',
                'base_price'   => 75000.00,
                'is_default'   => 0,
                'sort_order'   => 2,
                'status'       => 'active',
            ],
            [
                'package_id'   => $metalPkgId,
                'variant_name' => 'Oversized',
                'description'  => 'Larger size metal casket for oversized needs',
                'base_price'   => 200000.00,
                'is_default'   => 0,
                'sort_order'   => 3,
                'status'       => 'active',
            ],
        ];
        $this->db->table('package_variants')->insertBatch($variants);

        // Clear and insert package inclusions (8 items each, shared across packages)
        $this->db->table('package_inclusions')->where('inclusion_id >=', 0)->delete();

        $inclusions = [
            // Wood Casket inclusions
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Casket (Selected Variant)',
                'description' => 'Wood casket in the selected variant (Regular or Oversized)',
                'category'    => 'casket',
                'sort_order'  => 1,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Wake Facility (3 Days)',
                'description' => 'Use of chapel/wake facility for 3 days',
                'category'    => 'facility',
                'sort_order'  => 2,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Basic Embalming',
                'description' => 'Standard embalming and preparation services',
                'category'    => 'preparation',
                'sort_order'  => 3,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Hearse Rental (Local)',
                'description' => 'Transportation within city/municipality limits',
                'category'    => 'transport',
                'sort_order'  => 4,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Floral Arrangement (Standard)',
                'description' => 'Basic flower arrangement for the wake',
                'category'    => 'flowers',
                'sort_order'  => 5,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Memorial Card & Registry Book',
                'description' => 'Printed memorial cards and guest registry book',
                'category'    => 'stationery',
                'sort_order'  => 6,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Burial Permit Processing',
                'description' => 'Assistance with death certificate and burial permit',
                'category'    => 'documents',
                'sort_order'  => 7,
                'status'      => 'active',
            ],
            [
                'package_id'  => $woodPkgId,
                'item_name'   => 'Chapel Setup & Staffing',
                'description' => 'Wake facility setup with basic staffing',
                'category'    => 'staffing',
                'sort_order'  => 8,
                'status'      => 'active',
            ],
            // Metal Casket inclusions
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Casket (Selected Variant)',
                'description' => 'Metal casket in the selected variant (Half-Glass, Full-Glass, or Oversized)',
                'category'    => 'casket',
                'sort_order'  => 1,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Wake Facility (5 Days)',
                'description' => 'Use of premium chapel/wake facility for 5 days',
                'category'    => 'facility',
                'sort_order'  => 2,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Premium Embalming & Cosmetic Care',
                'description' => 'Enhanced embalming with cosmetic restoration',
                'category'    => 'preparation',
                'sort_order'  => 3,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Hearse & Family Car Rental (Local)',
                'description' => 'Premium transportation: hearse + family car within city/municipality',
                'category'    => 'transport',
                'sort_order'  => 4,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Floral Arrangement (Premium)',
                'description' => 'Premium flower arrangements for the wake',
                'category'    => 'flowers',
                'sort_order'  => 5,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Memorial Package (Cards, Registry, Thank You Cards)',
                'description' => 'Premium printed materials including thank you cards',
                'category'    => 'stationery',
                'sort_order'  => 6,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Burial Permit & Documentation',
                'description' => 'Complete documentation handling including permits',
                'category'    => 'documents',
                'sort_order'  => 7,
                'status'      => 'active',
            ],
            [
                'package_id'  => $metalPkgId,
                'item_name'   => 'Chapel Setup, Staffing & Coordination',
                'description' => 'Full wake facility setup with dedicated coordinator and staff',
                'category'    => 'staffing',
                'sort_order'  => 8,
                'status'      => 'active',
            ],
        ];
        $this->db->table('package_inclusions')->insertBatch($inclusions);

        // Clear and insert add-ons (Burial Attire)
        $this->db->table('add_ons')->where('addon_id >=', 0)->delete();

        $addOns = [
            [
                'addon_name'   => 'Burial Attire',
                'description'  => 'Clothing and accessories for the deceased',
                'base_price'   => 1500.00,
                'min_price'    => 1500.00,
                'max_price'    => 2000.00,
                'category'     => 'optional',
                'is_active'    => 1,
                'sort_order'   => 1,
            ],
        ];
        $this->db->table('add_ons')->insertBatch($addOns);

        // Clear and insert service rates for Balik Probinsya
        $this->db->table('service_rates')->where('rate_id >=', 0)->delete();

        // First ensure Balik Probinsya service exists in service_list
        $balikService = $this->db->table('service_list')->where('service_name', 'Balik Probinsya')->get()->getRow();
        if (! $balikService) {
            $this->db->table('service_list')->insert([
                'service_name'   => 'Balik Probinsya',
                'description'    => 'Transportation of deceased from Metro Manila/nearby provinces to Mindoro and other provinces',
                'base_price'     => 30000.00,
                'status'         => 'active',
                'is_available'   => 1,
            ]);
            $balikServiceId = $this->db->insertID();
        } else {
            $balikServiceId = (int) $balikService->service_list_id;
        }

        // Insert rates
        $serviceRates = [
            [
                'service_list_id' => $balikServiceId,
                'origin'          => 'Manila',
                'destination'     => 'Mindoro',
                'rate'            => 35000.00,
                'description'     => 'Manila to Mindoro (ferry + land transport)',
                'is_active'       => 1,
                'sort_order'      => 1,
            ],
            [
                'service_list_id' => $balikServiceId,
                'origin'          => 'Batangas',
                'destination'     => 'Mindoro',
                'rate'            => 30000.00,
                'description'     => 'Batangas to Mindoro (shorter ferry + land transport)',
                'is_active'       => 1,
                'sort_order'      => 2,
            ],
            [
                'service_list_id' => $balikServiceId,
                'origin'          => 'Manila',
                'destination'     => 'Other Provinces',
                'rate'            => 40000.00,
                'description'     => 'Manila to other provinces (custom quote)',
                'is_active'       => 1,
                'sort_order'      => 3,
            ],
            [
                'service_list_id' => $balikServiceId,
                'origin'          => 'Batangas',
                'destination'     => 'Other Provinces',
                'rate'            => 35000.00,
                'description'     => 'Batangas to other provinces (custom quote)',
                'is_active'       => 1,
                'sort_order'      => 4,
            ],
        ];
        $this->db->table('service_rates')->insertBatch($serviceRates);
    }
}