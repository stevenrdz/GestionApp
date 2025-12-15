import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NotificationService, Toast } from '../../service/notification';

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './notifications.html',
  styleUrl: './notifications.css',
})
export class Notifications {

  constructor(public notificationService: NotificationService) {}

  trackById(index: number, toast: Toast): number {
    return toast.id;
  }

  close(id: number): void {
    this.notificationService.remove(id);
  }
}
