import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { environment } from '../environments/environment';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { LoginComponent } from './login/login.component';
import { FooterComponent } from './footer/footer.component';

@Component({
 selector: 'app-root',
 standalone: true,
 imports: [RouterOutlet, CommonModule, LoginComponent,FooterComponent],
 templateUrl: './app.component.html',
 styleUrl: './app.component.css'
})
export class AppComponent {

  public environmentData: any = null;
  public apiSysinfo: any = null;
  
  constructor(private http: HttpClient)
  {
    this.environmentData =environment;
    this.http.get(environment.apiurl + 'sysinfo')
      .subscribe(
        (response: any) => {
          this.apiSysinfo = response;
        },
        (error) => {
          console.error(error);
        }
      );
  }
}
