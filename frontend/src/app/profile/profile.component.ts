import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '../user.service';  // Asegurate de que la ruta sea correcta
import { Router } from '@angular/router';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.css'
})
export class ProfileComponent implements OnInit {

  userNameWeb = '';
  email = '';
  nombre = '';
  apellido = '';
  avatar = '';
  fechaNacimiento = '';
  bio = '';
  rol = '';
  fechaRegistro = '';

  apiUrl = 'http://localhost:8012/miproyecto/api';


  constructor(private userService: UserService, private router: Router) {}

  ngOnInit(): void {
    this.cargarUsuario();
  }

  cargarUsuario(): void {
    this.userService.getUsuario().subscribe({
      next: (res) => {
        if (!res) return;

        console.log('Datos recibidos (profile):', res);

        this.userNameWeb = res.user_nameweb || '';
        this.email = res.email || '';
        this.nombre = res.nombre || '';
        this.apellido = res.apellido || '';
        this.avatar = res.avatar || '';
        this.fechaNacimiento = res.fecha_nacimiento || '';
        this.bio = res.bio || '';
        this.rol = res.rol || '';
        this.fechaRegistro = res.fecha_registro || '';
      },
      error: (err) => {
        console.error('Error al obtener datos del usuario (profile):', err);
      }
    });
  }

  irCambiarContrasena() {
  this.router.navigate(['/password-recup']);
}
}
