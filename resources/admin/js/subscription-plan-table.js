export default function subscriptionPlanTable() {
    return {
        search: '',
        currency: '',
        status: '',

        matches(plan) {
            const keyword = this.search.toLowerCase().trim();

            const label = String(plan.label || '').toLowerCase();
            const duration_days = String(plan.duration_days || '').toLowerCase();
            const price_for_local = String(plan.price_for_local || '').toLowerCase();
            const price_for_international = String(plan.price_for_international || '').toLowerCase();
            const status = String(plan.status || '');

            const matchesSearch =
                !keyword ||
                label.includes(keyword) ||
                duration_days.includes(keyword) ||
                price_for_local.includes(keyword) ||
                price_for_international.includes(keyword) ||
                status.includes(keyword);

            const matchesStatus =
                this.status === '' || status === this.status;

            return matchesSearch && matchesStatus;
        },
    };
}
