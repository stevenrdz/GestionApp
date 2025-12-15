import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  ApiService,
  Order,
  CreateOrderRequest,
  Client,
} from '../../service/api';
import { NotificationService } from '../../service/notification';
import { finalize } from 'rxjs/operators';

@Component({
  selector: 'app-orders-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './orders-list.html',
  styleUrl: './orders-list.css',
})
export class OrdersList implements OnInit {
  orders: Order[] = [];
  clients: Client[] = [];
  loading = false;
  error: string | null = null;

  filterStatus = '';
  filterClientId: number | null = null;

  showCreateForm = false;
  newOrder: CreateOrderRequest = {
    clientId: 0,
    totalAmount: 0,
    description: null,
  };

  constructor(
    private apiService: ApiService,
    private notificationService: NotificationService
  ) {}

  ngOnInit(): void {
    this.loadClients();
    this.loadOrders();
  }

  loadClients(): void {
    const start = performance.now();

    this.apiService.getClients().subscribe({
      next: (clients) => {
        this.clients = clients;
        console.log(
          '[OrdersList] Clientes cargados en ms:',
          performance.now() - start
        );
      },
      error: (err) => {
        console.warn('[OrdersList] Error cargando clientes', err);
        // no detenemos el módulo de pedidos por esto
      },
    });
  }

  loadOrders(): void {
    const start = performance.now();

    this.loading = true;
    this.error = null;

    const params: any = {};
    if (this.filterStatus) params.status = this.filterStatus;
    if (this.filterClientId) params.clientId = this.filterClientId;

    this.apiService
      .getOrders(params)
      .pipe(
        finalize(() => {
          this.loading = false;
          console.log(
            '[OrdersList] Pedidos cargados en ms:',
            performance.now() - start
          );
        })
      )
      .subscribe({
        next: (data) => {
          this.orders = data;
        },
        error: (err) => {
          console.error('[OrdersList] Error cargando pedidos', err);
          this.error = 'No se pudo cargar la lista de pedidos';
        },
      });
  }

  applyFilters(): void {
    this.loadOrders();
  }

  clearFilters(): void {
    this.filterStatus = '';
    this.filterClientId = null;
    this.loadOrders();
  }

  toggleCreateForm(): void {
    this.showCreateForm = !this.showCreateForm;
  }

  createOrder(): void {
    if (!this.newOrder.clientId || !this.newOrder.totalAmount) {
      this.notificationService.error('Selecciona cliente y monto total.');
      return;
    }
  
    const start = performance.now();
  
    this.apiService.createOrder(this.newOrder).subscribe({
      next: (res) => {
        console.log(
          '[OrdersList] Pedido creado en ms:',
          performance.now() - start
        );
  
        // Movemos cambios de UI al siguiente tick
        setTimeout(() => {
          this.notificationService.success(
            res.message || 'Pedido creado correctamente.'
          );
  
          this.showCreateForm = false;
          this.newOrder = {
            clientId: 0,
            totalAmount: 0,
            description: null,
          };
  
          this.loadOrders();
        }, 0);
      },
      error: (err) => {
        console.error('[OrdersList] Error creando pedido', err);
        const msg = err?.error?.message || 'No se pudo crear el pedido';
        this.notificationService.error(msg);
      },
    });
  }
  
  complete(order: Order): void {
    const start = performance.now();
  
    this.apiService.completeOrder(order.id).subscribe({
      next: (res) => {
        console.log(
          `[OrdersList] Pedido ${order.id} completado en ms:`,
          performance.now() - start
        );
  
        setTimeout(() => {
          this.notificationService.success(res.message || 'Pedido completado.');
          this.loadOrders();
        }, 0);
      },
      error: (err) => {
        console.error('[OrdersList] Error completando pedido', err);
        const msg = err?.error?.message || 'No se pudo completar el pedido';
        this.notificationService.error(msg);
      },
    });
  }
  
  cancel(order: Order): void {
    const start = performance.now();
  
    this.apiService.cancelOrder(order.id).subscribe({
      next: (res) => {
        console.log(
          `[OrdersList] Pedido ${order.id} cancelado en ms:`,
          performance.now() - start
        );
  
        setTimeout(() => {
          this.notificationService.success(res.message || 'Pedido cancelado.');
          this.loadOrders();
        }, 0);
      },
      error: (err) => {
        console.error('[OrdersList] Error cancelando pedido', err);
        const msg = err?.error?.message || 'No se pudo cancelar el pedido';
        this.notificationService.error(msg);
      },
    });
  }

  trackByOrderId(index: number, order: Order): number {
    return order.id;
  }
}
