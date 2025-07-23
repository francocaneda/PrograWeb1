import { Component, OnInit, OnDestroy, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { UserService } from '../user.service';
import { AuthService } from '../auth.service';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, RouterModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.css']
})
export class NavbarComponent implements OnInit, OnDestroy {

  userNameWeb = '';
  email = '';
  nombre = '';
  avatar = '';
  rol = '';
  termino: string = '';

  mostrarDropdown: boolean = false;
  postsFiltrados: any[] = [];

  mostrarNotificaciones = false;
  notificaciones: any[] = [];

  apiUrl = 'http://localhost:8012/miproyecto/api';

  private onClickOutsideHandler: any;

  constructor(
    private userService: UserService,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient,
    private elementRef: ElementRef
  ) { }

  ngOnInit(): void {
    this.userService.getUsuario().subscribe({
      next: (res) => {
        if (!res) return;

        this.userNameWeb = res.user_nameweb || '';
        this.email = res.email || '';
        this.nombre = res.nombre || '';
        this.avatar = res.avatar || '';
        this.rol = res.rol || '';
      },
      error: (err) => {
        console.error('Error al obtener el usuario desde UserService (navbar):', err);
      }
      
      
    });

    this.cargarNotificaciones();

    // Guardamos el handler para luego poder removerlo
    this.onClickOutsideHandler = this.onClickOutside.bind(this);
    document.addEventListener('click', this.onClickOutsideHandler);
  }

  ngOnDestroy(): void {
    // Removemos el listener usando la misma referencia
    document.removeEventListener('click', this.onClickOutsideHandler);
  }

  onClickOutside(event: MouseEvent): void {
    if (this.mostrarNotificaciones && !this.elementRef.nativeElement.contains(event.target)) {
      this.mostrarNotificaciones = false;
    }
  }

  get estaAutenticado(): boolean {
    return this.authService.estaAutenticado();
  }

  cerrarSesion(): void {
    this.authService.logout();
    this.userService.limpiarUsuario();
    this.router.navigate(['/loginscreen']);
  }

  buscarPosts(): void {
    if (this.termino.length < 1) {
      this.postsFiltrados = [];
      this.mostrarDropdown = false;
      return;
    }
    this.mostrarDropdown = true;

    this.http.get<{ posts: any[] }>(`${this.apiUrl}/index.php?comando=buscarPosts&query=${encodeURIComponent(this.termino)}`)
      .subscribe({
        next: (res) => {
          this.postsFiltrados = res.posts || [];
        },
        error: (err) => {
          console.error('Error al obtener posts para buscador', err);
          this.postsFiltrados = [];
        }
      });
  }

  irAlPost(id_post: string): void {
    this.mostrarDropdown = false;
    this.termino = '';
    this.postsFiltrados = [];
    this.router.navigate(['/main-layout/post', id_post]);
  }

  ocultarDropdownConDelay(): void {
    setTimeout(() => {
      this.mostrarDropdown = false;
    }, 200);
  }

  toggleNotificaciones(event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    this.mostrarNotificaciones = !this.mostrarNotificaciones;
    if (this.mostrarNotificaciones) {
      this.cargarNotificaciones();
    }
  }

  cargarNotificaciones(): void {
    const token = this.authService.getToken();
    if (!token) {
      this.notificaciones = [];
      return;
    }

    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    this.http.get<{ notificaciones: any[] }>(`${this.apiUrl}/index.php?comando=getNotificaciones`, { headers })
      .subscribe({
        next: (res) => {
          console.log('Notificaciones recibidas:', res.notificaciones);
          this.notificaciones = res.notificaciones || [];
        },
        error: (err) => {
          console.error('Error al cargar notificaciones:', err);
          this.notificaciones = [];
        }
      });
  }

  marcarComoLeida(noti: any): void {
    if (noti.leido === '1' || noti.leido === 1) {
      return; // Ya está marcada como leída
    }

    const token = this.authService.getToken();
    if (!token) return;

    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    this.http.patch(
      `${this.apiUrl}/index.php?comando=Notificaciones`,
      { id_notificacion: noti.id_notificacion },
      { headers }
    ).subscribe({
      next: () => {
        noti.leido = '1'; // Actualizamos estado local
        // Actualizamos la lista para refrescar el binding
        this.notificaciones = this.notificaciones.map(n => n.id_notificacion === noti.id_notificacion ? noti : n);
      },
      error: (err) => {
        console.error('Error al marcar notificación como leída:', err);
      }
    });
  }

  get cantidadNotificacionesNuevas(): number {
    return this.notificaciones.filter(n => n.leido === '0' || n.leido === 0).length;
  }

    esAdmin(): boolean {
  return this.authService.getRol() === 'admin';
}

}
