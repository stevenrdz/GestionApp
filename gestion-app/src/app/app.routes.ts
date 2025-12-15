import { Routes } from '@angular/router';
import { Login } from './auth/login/login';
import { Register } from './auth/register/register';
import { MainLayout } from './core/layout/layout';
import { Dashboard } from './features/dashboard/dashboard';
import { authGuard } from './core/guards/auth-guard';
import { ClientsList } from './features/clients/clients-list';
import { OrdersList } from './features/orders/orders-list';

export const routes: Routes = [
  {
    path: 'auth',
    children: [
      { path: 'login', component: Login },
      { path: 'register', component: Register },
    ],
  },
  {
    path: '',
    component: MainLayout,
    canActivate: [authGuard],
    children: [
      { path: 'dashboard', component: Dashboard },
      { path: 'clients', component: ClientsList },
      { path: 'orders', component: OrdersList },
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
