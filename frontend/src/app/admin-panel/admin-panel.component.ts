import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';      
import { UserService, Usuario } from '../user.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-admin-panel',
  standalone: true,                                  
  imports: [CommonModule],                           
  templateUrl: './admin-panel.component.html',
  styleUrls: ['./admin-panel.component.css']
})
export class AdminPanelComponent implements OnInit {
  usuarios: Usuario[] = [];

  constructor(private userService: UserService) {}

  ngOnInit(): void {
    this.cargarUsuarios();
  }

  cargarUsuarios(): void {
    this.userService.getUsuarios().subscribe({
      next: data => this.usuarios = data,
      error: err => console.error('Error al obtener usuarios', err)
    });
  }

eliminarUsuario(id_usuario: number): void {
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'No podrás revertir esta acción',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      this.userService.eliminarUsuario(id_usuario).subscribe({
        next: () => {
          Swal.fire('Eliminado', 'Usuario eliminado correctamente', 'success');
          this.cargarUsuarios();
        },
        error: err => {
          console.error('Error al eliminar usuario', err);
          Swal.fire('Error', 'No se pudo eliminar el usuario', 'error');
        }
      });
    }
  });
}

}
