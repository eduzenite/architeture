<?php

namespace App\Policies\Contracts;

use App\Models\Contracts\Contract;
use App\Models\User;

class ContractPolicy
{
    protected $contract;
    protected $user;

    /**
     * Create a new policy instance.
     */
    public function __construct(Contract $contract, User $user)
    {
        $this->contract = $contract;
        $this->user = $user;
    }

    public function viewAny()
    {
        return $this->user->hasPermission('view_any_contract');
    }

    public function view()
    {
        return $this->user->hasPermission('view_contract');
    }

    public function create()
    {
        return $this->user->hasPermission('create_contract');
    }

    public function update()
    {
        return $this->user->hasPermission('update_contract');
    }

    public function cancel()
    {
        return $this->user->hasPermission('cancel_contract');
    }

    public function delete()
    {
        return $this->user->hasPermission('delete_contract');
    }
}
