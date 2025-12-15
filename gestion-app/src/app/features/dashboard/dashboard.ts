// src/app/dashboard/dashboard.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService, DashboardStats } from '../../service/api';
import { finalize } from 'rxjs/operators';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard implements OnInit {
  stats: DashboardStats | null = null;
  loading = false;
  error: string | null = null;

  constructor(private apiService: ApiService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  private loadDashboard(): void {
    const start = performance.now();

    this.loading = true;
    this.error = null;

    this.apiService
      .getDashboard()
      .pipe(
        finalize(() => {
          this.loading = false;
          console.log(
            '[Dashboard] Datos cargados en ms:',
            performance.now() - start
          );
        })
      )
      .subscribe({
        next: (data) => {
          this.stats = data;
        },
        error: (err) => {
          console.error('[Dashboard] Error cargando datos', err);
          this.error = 'No se pudo cargar el dashboard';
        },
      });
  }
}
