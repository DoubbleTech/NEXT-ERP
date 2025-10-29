// app/(home)/page.js
// This file is a Server Component (default in the App Router)

// --- 1. IMPORT NECESSARY COMPONENTS AND SERVER LOGIC ---
import SetupWizard from '@/components/SetupWizard'; 
import MainDashboard from '@/components/Dashboard/MainDashboard'; // Assuming your tiles UI is here
// IMPORT BOTH ACTIONS from the ./actions.js file:
import { checkSetupStatus, completeSetup } from './actions'; 

// Existing imports (Keep these for unauthenticated users)
import Form from '@/components/Home/Form';
import Logo from '@/components/Home/logo';
import React from 'react';

// --- ASSUMPTION: Replace with your actual auth checker ---
// This function needs to be a secure, server-side method to check the user's login state.
async function getSession() {
    // Implement your logic to check if the user is logged in
    // This MUST return the actual session data or a minimum of { isLoggedIn: true, userId: X }
    
    // TEMPORARY MOCK FOR TESTING THE SETUP FLOW:
    // If you want to force the setup wizard: return { isLoggedIn: true, userId: 1 };
    // If you want to see the login screen: return { isLoggedIn: false };
    return { isLoggedIn: false }; // MOCK: Use your real implementation!
}
// --------------------------------------------------------


const Page = async () => {
    
    // Check authentication status
    const session = await getSession();

    // -----------------------------------------------------
    // PHASE 1: Authentication Check (For Unauthenticated Users)
    // -----------------------------------------------------
    if (!session.isLoggedIn) {
        // Renders your existing login/sign-up page content
        return (
            <section className='border-2 h-full my-10 max-w-6xl mx-auto shadow-[0_0_25px_rgba(0,0,0,0.2)] rounded-xl'>
                <div className="flex w-full gap-20">
                    {/* Logo */}
                    <div className='w-[500px] '>
                        <Logo />
                    </div>
                    {/* Form (Login/Sign-up) */}
                    <div className=' flex-1 pr-10'>
                        <Form />
                    </div>
                </div>
            </section>
        );
    }


    // -----------------------------------------------------
    // PHASE 2: Setup Check (For Authenticated Users)
    // -----------------------------------------------------
    
    // If the user IS logged in, check the setup status using the Server Action
    const status = await checkSetupStatus(session.userId); // Pass the user ID to the action

    if (status.setupRequired === true) {
        // If NO company is registered, render the client-side setup wizard.
        // We pass the completeSetup Server Action (imported from './actions') directly to the form component.
        return (
            <div className="setup-container">
                <SetupWizard completeSetupAction={completeSetup} />
            </div>
        );
    } 
    
    if (status.setupRequired === 'selection') {
        // If multiple companies exist, redirect to a company selector page
        return <div>Redirecting to Company Selection...</div>;
    }

    // -----------------------------------------------------
    // PHASE 3: Dashboard Render (Setup Complete)
    // -----------------------------------------------------
    
    // If setup is complete, render the main dashboard UI (the tiles screen)
    return (
        <MainDashboard />
    );
};

export default Page;
