import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { UserService } from '../user.service';
import { AuthService } from '../auth.service';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http'; 

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.css']
})
export class NavbarComponent implements OnInit {

  userNameWeb = '';
  email = '';
  nombre = '';
  avatar = '';
  rol = '';
  termino: string = '';

  mostrarDropdown: boolean = false;
  postsFiltrados: any[] = [];

  apiUrl = 'http://localhost:8012/miproyecto/api';

  constructor(
    private userService: UserService, 
    private authService: AuthService,  // privado
    private router: Router, 
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    this.userService.getUsuario().subscribe({
      next: (res) => {
        console.log('Respuesta del backend (navbar):', res);  // Debug

        const usuario = res;
        if (!usuario) return;

        this.userNameWeb = usuario.user_nameweb || '';
        this.email = usuario.email || '';
        this.nombre = usuario.nombre || '';
        this.avatar = usuario.avatar || '';
        this.rol = usuario.rol || '';
      },
      error: (err) => {
        console.error('Error al obtener el usuario desde UserService (navbar):', err);
      }
    });
  }

  // Getter público para usar en el template
  get estaAutenticado(): boolean {
    return this.authService.estaAutenticado();
  }

  cerrarSesion(): void {
    this.authService.logout();
    this.userService.limpiarUsuario(); // Limpia los datos del usuario
    console.log('Sesión cerrada y datos de usuario limpiados');
    this.router.navigate(['/loginscreen']); // Redirige a login o donde corresponda
  }

  buscarPosts(): void {
    if (this.termino.length < 1) {
      this.postsFiltrados = [];
      this.mostrarDropdown = false;
      return;
    }
    this.mostrarDropdown = true;

    this.http.get<{posts: any[]}>(`${this.apiUrl}/index.php?comando=buscarPosts&query=${encodeURIComponent(this.termino)}`)
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
    this.router.navigate(['/post', id_post]);
  }

  ocultarDropdownConDelay(): void {
    setTimeout(() => {
      this.mostrarDropdown = false;
    }, 200);
  }
}
