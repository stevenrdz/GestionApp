import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  ApiService,
  Client,
  CreateClientRequest,
  CreateClientResponse,
} from '../../service/api';
import { NotificationService } from '../../service/notification';
import { finalize } from 'rxjs/operators';

@Component({
  selector: 'app-clients-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './clients-list.html',
  styleUrl: './clients-list.css',
})
export class ClientsList implements OnInit {
  clients: Client[] = [];
  loading = false;
  error: string | null = null;

  showCreateForm = false;
  newClient: CreateClientRequest = {
    firstName: '',
    lastName: '',
    email: '',
    document: '',
    phone: null,
    address: null,
  };

  constructor(
    private apiService: ApiService,
    private notificationService: NotificationService
  ) {}

  ngOnInit(): void {
    this.loadClients();
  }

  loadClients(): void {
    const start = performance.now();

    this.loading = true;
    this.error = null;

    this.apiService
      .getClients()
      .pipe(
        finalize(() => {
          this.loading = false;
          console.log(
            '[ClientsList] Clientes cargados en ms:',
            performance.now() - start
          );
        })
      )
      .subscribe({
        next: (data) => {
          this.clients = data;
        },
        error: (err) => {
          console.error('[ClientsList] Error cargando clientes', err);
          this.error = 'No se pudo cargar la lista de clientes';
        },
      });
  }

  toggleCreateForm(): void {
    this.showCreateForm = !this.showCreateForm;
  }

  createClient(): void {
    if (
      !this.newClient.firstName ||
      !this.newClient.lastName ||
      !this.newClient.email ||
      !this.newClient.document
    ) {
      this.notificationService.error(
        'Completa al menos nombre, apellido, email y documento.'
      );
      return;
    }

    const start = performance.now();

    this.apiService.createClient(this.newClient).subscribe({
      next: (res: CreateClientResponse) => {
        console.log(
          '[ClientsList] Cliente creado en ms:',
          performance.now() - start
        );

        this.notificationService.success(
          res.message || 'Cliente creado correctamente.'
        );

        // En lugar de recargar toda la lista, agregamos el cliente localmente.
        // Ajusta estos defaults a lo que tu backend realmente usa.
        const createdAt = new Date().toISOString();
        const newClientAsEntity: Client = {
          id: res.id,
          firstName: this.newClient.firstName,
          lastName: this.newClient.lastName,
          email: this.newClient.email,
          document: this.newClient.document,
          phone: this.newClient.phone ?? null,
          nationalId: null,
          address: this.newClient.address ?? null,
          typeDocument: 'ID',      // o el que uses por defecto
          status: 'ACTIVE',        // o el que uses por defecto
          createdAt,
          updatedAt: null,
        };

        this.clients = [...this.clients, newClientAsEntity];

        this.showCreateForm = false;
        this.newClient = {
          firstName: '',
          lastName: '',
          email: '',
          document: '',
          phone: null,
          address: null,
        };
      },
      error: (err) => {
        console.error('[ClientsList] Error creando cliente', err);
        const msg = err?.error?.message || 'No se pudo crear el cliente';
        this.notificationService.error(msg);
      },
    });
  }

  // Recomendado: usar esto en el *ngFor del template para mejorar renderizado
  trackByClientId(index: number, client: Client): number {
    return client.id;
  }
}
