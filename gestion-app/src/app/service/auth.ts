// src/app/service/auth.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';

export interface LoginRequest {
  userNameOrEmail: string;
  password: string;
}

export interface LoginResponse {
  accessToken: string | null;
  tokenType: string | null;
  expiresIn: number;
  refreshToken: string | null;
}

export interface RegisterRequest {
  userName: string;
  email: string;
  password: string;
}

const ACCESS_TOKEN_KEY = 'Kx9pL3rQ8nV1zT6wM2bF4hR7cY5gP0kA3sD9jL8uN2rX7v';

@Injectable({
  providedIn: 'root',
})
export class AuthService {

  private currentTokenSubject = new BehaviorSubject<string | null>(this.getTokenFromStorage());
  currentToken$ = this.currentTokenSubject.asObservable();

  constructor(private http: HttpClient) {}

  register(payload: RegisterRequest): Observable<any> {
    return this.http.post('/auth-api/api/auth/register', payload);
  }

  login(payload: LoginRequest): Observable<LoginResponse> {
    return this.http.post<LoginResponse>('/auth-api/api/auth/login', payload)
      .pipe(
        tap(response => {
          if (response.accessToken) {
            localStorage.setItem(ACCESS_TOKEN_KEY, response.accessToken);
            this.currentTokenSubject.next(response.accessToken);
          }
        })
      );
  }

  logout(): void {
    localStorage.removeItem(ACCESS_TOKEN_KEY);
    this.currentTokenSubject.next(null);
  }

  getToken(): string | null {
    return this.currentTokenSubject.value;
  }

  private getTokenFromStorage(): string | null {
    return localStorage.getItem(ACCESS_TOKEN_KEY);
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }
}
