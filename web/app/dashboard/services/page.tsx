import { ServiceEditor } from '@/components/ServiceEditor';
import { getMyCompany } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export const metadata = { title: 'Услуги — кабинет' };

export default async function DashboardServices() {
  const token = await getToken();
  const company = await getMyCompany(token!);

  return (
    <section className="profile-section">
      <h2>Услуги компании</h2>
      <ServiceEditor services={company?.services ?? []} />
    </section>
  );
}
