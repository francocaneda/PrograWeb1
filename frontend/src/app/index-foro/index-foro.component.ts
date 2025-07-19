import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { UserService } from '../user.service';

@Component({
  selector: 'app-index-foro',
  standalone: true,
  imports: [CommonModule, FormsModule, HttpClientModule],
  templateUrl: './index-foro.component.html',
  styleUrl: './index-foro.component.css'
})
export class IndexForoComponent implements OnInit {

  userNameWeb = '';
  email = '';
  nombre = '';
  apellido = '';
  avatar = '';
  fechaNacimiento = '';
  bio = '';
  rol = '';

  miembrosAntiguos: any[] = [];

  totalUsuarios: number = 0;
  totalPosts: number = 0;
  totalComentarios: number = 0;

  estaLogueado: boolean = false;  // <-- NUEVA propiedad

  constructor(private userService: UserService) {}

  ngOnInit(): void {
    // Usuario logueado
    this.userService.getUsuario().subscribe({
      next: (res) => {
        if (!res) {
          this.estaLogueado = false;
          return;
        }

        this.estaLogueado = true; // <-- Activamos la bandera

        this.userNameWeb = res.user_nameweb || '';
        this.email = res.email || '';
        this.nombre = res.nombre || '';
        this.apellido = res.apellido || '';
        this.avatar = res.avatar || '';
        this.fechaNacimiento = res.fecha_nacimiento || '';
        this.bio = res.bio || '';
        this.rol = res.rol || '';

        // Solo cargamos miembros antiguos si está logueado
        this.cargarMiembrosAntiguos();
      },
      error: (err) => {
        console.error('Error al obtener usuario desde UserService:', err);
        this.estaLogueado = false;
      }
    });

    // Totales generales (no requieren login)
    this.userService.getTotalUsuarios().subscribe({
      next: (res) => {
        this.totalUsuarios = res.total_usuarios;
      },
      error: (err) => {
        console.error('Error al cargar total de usuarios:', err);
      }
    });

    this.userService.getTotalPosts().subscribe({
      next: (res) => {
        this.totalPosts = res.total_posts;
      },
      error: (err) => {
        console.error('Error al cargar total de posts:', err);
      }
    });

    this.userService.getTotalComentarios().subscribe({
      next: (res) => {
        this.totalComentarios = res.total_comentarios;
      },
      error: (err) => {
        console.error('Error al cargar total de comentarios:', err);
      }
    });
  }

  cargarMiembrosAntiguos(): void {
    this.userService.getUsuariosAntiguos().subscribe({
      next: (res) => {
        const iconos = ['🧑‍🎓', '🧑‍💻', '🧙', '👤', '👥'];

        this.miembrosAntiguos = res.usuarios_antiguos.map((u: any) => {
          const fecha = new Date(u.fecha_registro);
          const opciones: Intl.DateTimeFormatOptions = {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
          };
          const fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);

          return {
            nombre: u.nombre_completo,
            fecha: fechaFormateada,
            icono: iconos[Math.floor(Math.random() * iconos.length)]
          };
        });
      },
      error: (err) => {
        console.error('Error al cargar miembros antiguos:', err);
      }
    });
  }

  handleSubmit(event: Event) {
    event.preventDefault();
    const form = event.target as HTMLFormElement;
    form.reset();
  }
}