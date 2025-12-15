import { Injectable, signal } from '@angular/core';

export type ToastType = 'success' | 'error' | 'info';

export interface Toast {
  id: number;
  type: ToastType;
  message: string;
}

@Injectable({
  providedIn: 'root',
})
export class NotificationService {
  private _toasts = signal<Toast[]>([]);
  private _idCounter = 0;

  // signal de solo lectura
  readonly toasts = this._toasts.asReadonly();

  private show(type: ToastType, message: string, durationMs = 4000): void {
    const id = ++this._idCounter;
    const toast: Toast = { id, type, message };

    this._toasts.update((current) => [...current, toast]);

    if (durationMs > 0) {
      setTimeout(() => this.remove(id), durationMs);
    }
  }

  success(message: string, durationMs = 4000): void {
    this.show('success', message, durationMs);
  }

  error(message: string, durationMs = 4000): void {
    this.show('error', message, durationMs);
  }

  info(message: string, durationMs = 4000): void {
    this.show('info', message, durationMs);
  }

  remove(id: number): void {
    this._toasts.update((current) => current.filter((t) => t.id !== id));
  }
}
