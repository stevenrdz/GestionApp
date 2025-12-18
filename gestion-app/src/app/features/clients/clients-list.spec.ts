import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { vi } from 'vitest';

import { ClientsList } from './clients-list';
import { ApiService } from '../../service/api';

describe('ClientsList', () => {
  let component: ClientsList;
  let fixture: ComponentFixture<ClientsList>;

  const api = {
    getClients: vi.fn(),
  } as unknown as ApiService;

  beforeEach(async () => {
    vi.clearAllMocks();

    // Silenciar consola del componente (opcional pero recomendado)
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});

    (api.getClients as any).mockReturnValue(of([]));

    await TestBed.configureTestingModule({
      imports: [ClientsList],
      providers: [{ provide: ApiService, useValue: api }],
    }).compileComponents();

    fixture = TestBed.createComponent(ClientsList);
    component = fixture.componentInstance;

    // Importante: dispara ngOnInit
    fixture.detectChanges();
    await fixture.whenStable();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('debería crearse el componente', () => {
    expect(component).toBeTruthy();
  });

  it('debería solicitar la lista de clientes al iniciar', () => {
    expect(api.getClients).toHaveBeenCalled();
  });
});
