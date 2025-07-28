import { List, Datagrid, TextField, EmailField, EditButton } from 'react-admin';

const UserList = () => (
    <List>
        <Datagrid rowClick="show">
            <TextField source="id" />
            <EmailField source="email" />
            <TextField source="role" />
            <EditButton />
        </Datagrid>
    </List>
);

export default UserList;
