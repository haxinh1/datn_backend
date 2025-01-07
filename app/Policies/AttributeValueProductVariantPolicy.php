<?php

namespace App\Policies;

use App\Models\Attribute_value_product_variant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttributeValueProductVariantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attribute_value_product_variant $attributeValueProductVariant): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attribute_value_product_variant $attributeValueProductVariant): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attribute_value_product_variant $attributeValueProductVariant): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attribute_value_product_variant $attributeValueProductVariant): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attribute_value_product_variant $attributeValueProductVariant): bool
    {
        //
    }
}
