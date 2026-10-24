import '../../users/js/modalScrollLock.js';
import Alpine from 'alpinejs';

import subscriptionPlanTable from './subscription-plan-table';

window.Alpine = Alpine;
Alpine.data('subscriptionPlanTable', subscriptionPlanTable);

Alpine.start();
