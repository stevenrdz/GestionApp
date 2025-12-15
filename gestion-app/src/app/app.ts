import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Notifications } from './features/notifications/notifications';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, Notifications],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  protected readonly title = signal('gestion-app');
}
