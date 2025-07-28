import type { DataProvider } from "react-admin";
import http from './httpClient';

const dataProvider: DataProvider = {
    getList: async (resource, params) => {
        const { page = 1, perPage = 10 } = (params.pagination ?? {}) as { page?: number; perPage?: number };
        const { field = 'id', order = 'ASC' } = (params.sort ?? {}) as { field?: string; order?: string };
        const { data } = await http.get(`/${resource}`, {
            params: {
                page,
                limit: perPage,
                sort: `${field},${order.toLowerCase()}`,
                ...params.filter,
            },
        });
        return {
            data: data.items,
            total: data.total,
        };
    },
    getOne: async (resource, params) => {
        const { data } = await http.get(`/${resource}/${params.id}`);
        return { data };
    },
    create: async (resource, params) => {
        const { data } = await http.post(`/${resource}`, params.data);
        return { data };
    },
    update: async (resource, params) => {
        const { data } = await http.put(`/${resource}/${params.id}`, params.data);
        return { data };
    },
    delete: async (resource, params) => {
        const { data } = await http.delete(`/${resource}/${params.id}`);
        return { data };
    },
    getMany: async (resource, params) => {
        const { data } = await http.get(`/${resource}`, { params: { filter: { id: params.ids } } });
        return { data: data.items };
    },
    getManyReference: async (resource, params) => {
        const { page = 1, perPage = 10 } = (params.pagination ?? {}) as { page?: number; perPage?: number };
        const { field = 'id', order = 'ASC' } = (params.sort ?? {}) as { field?: string; order?: string };
        const filter = { ...params.filter, [params.target]: params.id };
        const { data } = await http.get(`/${resource}`, {
            params: {
                page,
                limit: perPage,
                sort: `${field},${order.toLowerCase()}`,
                ...filter,
            },
        });
        return { data: data.items, total: data.total };
    },

    updateMany: async (resource, params) => {
        const { data } = await http.put(`/${resource}`, { ids: params.ids, data: params.data });
        return { data: data.items };
    },
    deleteMany: async (resource, params) => {
        const { data } = await http.delete(`/${resource}`, { data: { ids: params.ids } });
        return { data: data.items };
    },
};
export default dataProvider;
