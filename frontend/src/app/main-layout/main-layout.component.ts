import { Component, OnInit } from '@angular/core';
import { NavbarComponent } from '../navbar/navbar.component';
import { FooterComponent } from '../footer/footer.component';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [NavbarComponent, FooterComponent, RouterOutlet, CommonModule],
  templateUrl: './main-layout.component.html',
  styleUrls: ['./main-layout.component.css'],
})
export class MainLayoutComponent implements OnInit {

  constructor(private router: Router) {
    // Redirige a la ruta 'index' al cargar el componente

  }
  ngOnInit(): void {
    this.router.navigate(['main-layout/index'])
  }

}

// Este componente sirve como layout principal de la aplicación, incluyendo la barra de navegación y el pie de página.
// Utiliza el componente RouterOutlet para cargar las rutas hijas dentro de este layout.