import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { vi } from 'vitest';

import { Dashboard } from './dashboard';
import { ApiService } from '../../service/api';

describe('Dashboard', () => {
  let component: Dashboard;
  let fixture: ComponentFixture<Dashboard>;

  const api = {
    getDashboard: vi.fn(),
  } as unknown as ApiService;

  beforeEach(async () => {
    vi.clearAllMocks();

    // Silenciar consola del componente (opcional)
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});

    (api.getDashboard as any).mockReturnValue(of({
      totalOrders: 0,
      completedOrders: 0,
      pendingOrders: 0,
      activeClients: 0,
      dailyActivity: [],
      monthlyActivity: [],
    }));

    await TestBed.configureTestingModule({
      imports: [Dashboard],
      providers: [{ provide: ApiService, useValue: api }],
    }).compileComponents();

    fixture = TestBed.createComponent(Dashboard);
    component = fixture.componentInstance;

    fixture.detectChanges();
    await fixture.whenStable();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('debería crearse el componente', () => {
    expect(component).toBeTruthy();
    expect(api.getDashboard).toHaveBeenCalled();
  });
});
