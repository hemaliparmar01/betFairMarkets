<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Repositories\Admin\UserManagementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    public function __construct(
        private readonly UserManagementRepository $userManagementRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->userManagementRepository->getAll($filters);
    }

    public function getStats(): array
    {
        return $this->userManagementRepository->getStats();
    }

    public function findById(Customer $customer): array
    {
        return $this->userManagementRepository->findById($customer);
    }

    public function create(array $data): Customer
    {
        return DB::transaction(fn (): Customer => $this->userManagementRepository->create($data));
    }

    public function update(Customer $customer, array $data): Customer
    {
        return Cache::lock('subscription-customer:'.$customer->id, 120)->block(5, fn (): Customer => DB::transaction(fn (): Customer => $this->userManagementRepository->update($customer, $data)));
    }
}
