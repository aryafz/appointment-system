import { Admin, Resource } from 'react-admin';
import { BrowserRouter } from 'react-router-dom';
import authProvider from './auth/authProvider';
import dataProvider from './data/dataProvider';
import Dashboard from './pages/Dashboard';
import UserResource from './resources/users';
import TenantSelector from './components/TenantSelector';

import MyLogin from "./pages/Login";
const App = () => (
    <BrowserRouter>
        <Admin dashboard={Dashboard} authProvider={authProvider} dataProvider={dataProvider} loginPage={MyLogin}>
            <Resource name="users" {...UserResource} />
        </Admin>
        <TenantSelector />
    </BrowserRouter>
);

export default App;
